<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\Variants;


class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

    public function usage() { 
        return 'phpbrew install [php-version] ([variants...])';
    }

    public function options($opts)
    {
        $opts->add('no-test','No tests');
        $opts->add('production','Use production configuration');
        $opts->add('nice:', 'process nice level');
    }

    public function execute($version)
    {
        $options = $this->getOptions();
        $logger = $this->getLogger();

        // get extra arguments 
        $extraArgs = func_get_args();
        array_shift($extraArgs);


        $info = \PhpBrew\PhpSource::getVersionInfo( $version );
        if( ! $info)
            throw new Exception("Version $version not found.");

        $home = Config::getPhpbrewRoot();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getVersionBuildPrefix( $version );

        if( ! file_exists($buildDir) )
            mkdir( $buildDir, 0755, true );

        if( ! file_exists($buildPrefix) )
            mkdir( $buildPrefix, 0755, true );

        chdir( $buildDir );

        // xxx: refactor this
        $targetDir = null;
        if( isset($info['url']) ) {
            $downloader = new \PhpBrew\Downloader\UrlDownloader( $logger );
            $targetDir = $downloader->download( $info['url'] );
        }
        elseif( isset($info['svn']) ) {
            $downloader = new \PhpBrew\Downloader\SvnDownloader( $logger );
            $targetDir = $downloader->download( $info['svn'] );
        }

        if( ! file_exists($targetDir ) ) 
            throw new Exception("Download failed.");

        $builder = new \PhpBrew\Builder( $targetDir, $version );
        $builder->logger = $this->getLogger();
        $builder->options = $options;

        $builder->clean();
        $builder->configure();



        $logger->info("===> Building $version...");
        $command = 'make';
        if( $options->nice )
            $command = 'nice -n ' . $options->nice->value . ' ' . $command;

        $startTime = microtime(true);
        system( $command . ' > /dev/null' ) !== false or die('Make failed.');

        if( $options->{'no-test'} ) {
            $logger->info("Skip tests");
        } else {
            $logger->info("Testing");

            $command = 'make test';
            if( $options->nice )
                $command = 'nice -n ' . $options->nice->value . ' ' . $command;
            system( $command . ' > /dev/null' ) !== false or die('Test failed.');
        }

        $buildTime = (int)((microtime(true) - $startTime) / 60);
        $logger->info("Build finished: $buildTime minutes.");

        $logger->info("===> Installing...");
        system( 'make install > /dev/null' ) !== false or die('Install failed.');


        $dSYM = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php.dSYM';
        if ( file_exists($dSYM)) {
            $php = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
            rename( $dSYM , $php );
        }


        $phpConfigFile = $options->production ? 'php.ini-production' : 'php.ini-development';
        $logger->info("===> Copying $phpConfigFile ...");
        if( file_exists($phpConfigFile) ) {
            if( ! file_exists( Config::getVersionEtcPath($version) ) )
                mkdir( Config::getVersionEtcPath($version) , 0755 , true );
            rename( $phpConfigFile , Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php.ini' );
        }

        $logger->info("Done!");

        echo <<<EOT
To use the newly built PHP, try the line(s) below:

    $ phpbrew use $version

Or you can use switch command to switch your default php version to $version:

    $ phpbrew switch $version

Enjoy!
EOT;
    }
}


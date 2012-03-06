<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\Variants;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;

class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

    public function usage() { 
        return 'phpbrew install [php-version] ([+variant...])';
    }

    public function options($opts)
    {
        $opts->add('no-test','No tests');
        $opts->add('no-clean','Do not clean object files before/after building.');
        $opts->add('production','Use production configuration');
        $opts->add('nice:', 'process nice level');
    }

    public function execute($version)
    {
        $options = $this->getOptions();
        $logger = $this->getLogger();

        // get extra options for building php  
        $extra = array();
        $args = func_get_args();
        array_shift($args);

        // split variant strings
        $isVariant = true;
        $tmp = array();
        foreach( $args as $a ) {
            if( $a == '--' ) {
                $isVariant = false;
                continue;
            }

            if( $isVariant ) {
                $a = array_filter(explode('+',$a), function($a) { return $a ? true : false; });
                $tmp = array_merge( $tmp , $a );
            }
            else {
                $extra[] = $a;
            }
        }
        $args = $tmp;




        $info = PhpSource::getVersionInfo( $version );
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
        $builder->logger = $logger;
        $builder->options = $options;

        $logger->info( 'Build Dir: ' . realpath($buildDir . DIRECTORY_SEPARATOR . $targetDir) );

        // strip plus sign.
        foreach( $args as $a ) {
            $a = preg_replace( '/^\+/', '', $a );
            $builder->addVariant( $a );
        }

        if( ! $options->{'no-clean'} ) 
            $builder->clean();

        $builder->configure( $extra );

        $logger->info("===> Building $version...");

        $cmd = new CommandBuilder('make');
        $cmd->append = true;
        $cmd->redirectStderrToStdout = true;
        $cmd->stdout = Config::getVersionBuildLogPath( $version );
        if( $options->nice )
            $cmd->nice( $options->nice->value );


        $startTime = microtime(true);

        $logger->debug( '' .  $cmd  );
        $cmd->execute() !== false or die('Make failed.');

        if( $options->{'no-test'} ) {
            $logger->info("Skip tests");
        } else {
            $logger->info("Testing");

            $cmd = new CommandBuilder('make test');
            if( $options->nice )
                $cmd->nice( $options->nice->value );
            $cmd->redirectStderrToStdout = true;
            $cmd->append = true;
            $cmd->stdout = Config::getVersionBuildLogPath( $version );
            $logger->debug( '' .  $cmd  );
            $cmd->execute() !== false or die('Test failed.');
        }

        $buildTime = (int)((microtime(true) - $startTime) / 60);
        $logger->info("Build finished: $buildTime minutes.");

        $logger->info("===> Installing...");

        $install = new CommandBuilder('make install');
        $install->redirectStderrToStdout = true;
        $install->append = true;
        $install->stdout = Config::getVersionBuildLogPath( $version );
        $install->execute() !== false or die('Install failed.');

        /*
        if( ! $options->{'no-clean'} ) 
            $builder->clean();
        */

        /** POST INSTALLATION **/


        /* Check if php.dSYM exists */
        $dSYM = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php.dSYM';
        if ( file_exists($dSYM)) {
            $logger->info("---> Moving php.dSYM to php ");
            $php = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
            rename( $dSYM , $php );
        }



        $phpConfigFile = $options->production ? 'php.ini-production' : 'php.ini-development';
        $logger->info("---> Copying $phpConfigFile ");
        if( file_exists($phpConfigFile) ) {
            if( ! file_exists( Config::getVersionEtcPath($version) ) )
                mkdir( Config::getVersionEtcPath($version) , 0755 , true );
            rename( $phpConfigFile , Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php.ini' );
        }

        $logger->info("Source directory: " . realpath( $targetDir ) );

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


<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\Variants;


class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

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

        // switching to $version build directory
        chdir($targetDir);


        /**
         * xxx: 
         * PHP_AUTOCONF=autoconf213 ./buildconf --force
         */
        if( ! file_exists('configure') )
            system('./buildconf');


        // build configure args
        // XXX: support variants
        $args = array();
        putenv('CFLAGS=-O3');
        $args[] = './configure';

        $args[] = "--prefix=$buildPrefix";
        $args[] = "--with-config-file-path=$buildPrefix/etc";
        $args[] = "--with-config-file-scan-dir=$buildPrefix/var/db";
        $args[] = "--with-pear=$buildPrefix/lib/php";

        $variants = new Variants();

        // XXX: detect include prefix
        $args[] = "--disable-all";
        $args = array_merge( $args , $variants->getCommonOptions() );


        $logger->info("Configuring $version...");
        $command = join(' ', array_map( function($val) { return escapeshellarg($val); }, $args) );

        $logger->debug( $command );

        if( $options->nice )
            $command = 'nice -n ' . $options->nice->value . ' ' . $command;

        system( $command . ' > /dev/null' ) !== 0 or die('Configure failed.');

        $logger->info("Building php-$version...");
        $command = 'make';
        if( $options->nice )
            $command = 'nice -n ' . $options->nice->value . ' ' . $command;
        system( $command . ' > /dev/null' ) !== 0 or die('Make failed.');

        if( $options->{'no-test'} ) {
            $logger->info("Skip tests");
        } else {
            $logger->info("Testing");

            $command = 'make test';
            if( $options->nice )
                $command = 'nice -n ' . $options->nice->value . ' ' . $command;
            system( $command . ' > /dev/null' ) !== 0 or die('Test failed.');
        }

        $logger->info("Installing");
        system( 'make install > /dev/null' ) !== 0 or die('Install failed.');


        $dSYM = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php.dSYM';
        if ( file_exists($dSYM)) {
            $php = $buildPrefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php';
            rename( $dSYM , $php );
        }


        $phpConfigFile = $options->production ? 'php.ini-production' : 'php.ini-development';
        $logger->info("Copying $phpConfigFile ...");
        if( file_exists($phpConfigFile) ) {
            rename( $phpConfigFile , Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php.ini' );
        }

        $logger->info("Done");
    }
}


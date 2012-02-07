<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;

class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

    public function options($opts)
    {
        $opts->add('no-test','No tests');
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



        // build configure args
        // XXX: support variants
        $args = array();
        $args[] = './configure';

        $args[] = "--prefix=$buildPrefix";
        $args[] = "--with-config-file-path=$buildPrefix/etc";
        $args[] = "--with-config-file-scan-dir=$buildPrefix/var/db";
        $args[] = "--with-pear=$buildPrefix/lib/php";


        // XXX: detect include prefix
        $args[] = "--with-curl";
        $args[] = "--with-bz2";
        $args[] = "--with-mhash";
        $args[] = "--with-pcre-regex";
        $args[] = "--with-readline";
        $args[] = "--with-zlib";
        $args[] = "--with-gettext=/opt/local";
        $args[] = "--with-libxml-dir=/opt/local";

        $args[] = "--disable-all";
        $args[] = "--enable-bcmath";
        $args[] = "--enable-zip";
        $args[] = "--enable-ctype";
        $args[] = "--enable-dom";
        $args[] = "--enable-fileinfo";
        $args[] = "--enable-filter";
        $args[] = "--enable-hash";
        $args[] = "--enable-json";
        $args[] = "--enable-libxml";
        $args[] = "--enable-phar";
        $args[] = "--enable-session";
        $args[] = "--enable-simplexml";
        $args[] = "--enable-tokenizer";
        $args[] = "--enable-xml";
        $args[] = "--enable-xmlreader";
        $args[] = "--enable-xmlwriter";
        $args[] = "--enable-cli";
        $args[] = "--enable-intl";
        $args[] = "--enable-mbstring";
        $args[] = "--enable-mbregex";
        $args[] = "--enable-sockets";
        $args[] = "--enable-exif";
        $args[] = "--enable-short-tags";
        $args[] = "--enable-pdo";


        // XXX: add variants for this
        $args[] = "--with-mysql";
        $args[] = "--with-mysqli";
        $args[] = "--disable-cgi";
        $args[] = "--enable-shmop";
        $args[] = "--enable-sysvsem";
        $args[] = "--enable-sysvshm";
        $args[] = "--enable-sysvmsg";

        $logger->info("Configuring php-$version...");
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

        $logger->info("Done");
    }
}


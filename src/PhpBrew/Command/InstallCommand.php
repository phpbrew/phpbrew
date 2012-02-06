<?php
namespace PhpBrew\Command;
use Exception;

class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

    public function options($opts)
    {
        $opts->add('no-test','No tests');
    }

    public function execute($version)
    {
        $options = $this->getOptions();
        $logger = $this->getLogger();
        $versions = \PhpBrew\PhpStas::getVersions();
        if( ! isset($versions[$version] ) )
            throw new Exception("Version $version not found.");

        $url = $versions[ $version ];

        $home = getenv('HOME') . DIRECTORY_SEPARATOR . '.phpbrew';
        $buildDir = $home . DIRECTORY_SEPARATOR . 'build';
        $buildPrefix = $home . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . $version;

        if( ! file_exists($buildDir) )
            mkdir( $buildDir, 0755, true );

        if( ! file_exists($buildPrefix) )
            mkdir( $buildPrefix, 0755, true );


        chdir( $buildDir );

        $parts = parse_url($url);
        $basename = basename( $parts['path'] );

        $logger->info("Downloading $url");
        // system( 'curl -# -O ' . $url );


        $logger->info("Extracting...");
        // system( "tar xzf $basename" );

        // var_dump( pathinfo( $basename ) );
        $dir = substr( $basename , 0 , strpos( $basename , '.tar.bz2' ) );

        // switching to $version build directory
        chdir($dir);








        // build configure args
        // XXX: support variants
        $args = array();
        $args[] = './configure';

        $args[] = "--prefix=$buildPrefix";
        $args[] = "--with-config-file-path=$buildPrefix/etc";
        $args[] = "--with-config-file-scan-dir=$buildPrefix/var/db";
        $args[] = "--with-pear=$buildPrefix/lib/php";


        // XXX: detect include prefix
        $args[] = "--with-curl=/opt/local";
        $args[] = "--with-bz2=/opt/local";
        $args[] = "--with-mhash=/opt/local";
        $args[] = "--with-pcre-regex=/opt/local";
        $args[] = "--with-readline=/opt/local";
        $args[] = "--with-libxml-dir=/opt/local";
        $args[] = "--with-zlib=/opt/local";
        $args[] = "--with-gettext=/opt/local";

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
        $args[] = "--enable-pdo";
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
        system( $command . ' > /dev/null' );

        $logger->info("Building php-$version...");
        system( 'make > /dev/null' );

        if( $options->{'no-test'} ) {
            $logger->info("Skip tests");
        } else {
            $logger->info("Testing");
            system( 'make test > /dev/null' );
        }

        $logger->info("Installing");
        system( 'make install > /dev/null' );
    }
}





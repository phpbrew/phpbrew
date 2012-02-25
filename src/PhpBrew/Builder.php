<?php
namespace PhpBrew;
use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\Variants;
use PhpBrew\CommandBuilder;

class Builder
{


    /**
     * @var CLIFramework\Logger logger object
     */
    public $logger;

    public $options;

    /**
     * @var string Version string
     */
    public $version;


    /**
     * @var string source code directory, path to extracted source directory
     */
    public $targetDir;

    /**
     * @var source build directory
     */
    public $buildDir;

    /**
     * @var string install prefix path
     */
    public $buildPrefix;

    /**
     * @var string phpbrew root
     */
    public $root;

    public function __construct($targetDir,$version)
    {
        $this->targetDir = $targetDir;
        $this->root = Config::getPhpbrewRoot();
        $this->buildDir = Config::getBuildDir();
        $this->buildPrefix = Config::getVersionBuildPrefix( $version );
        $this->version = $version;
        chdir($targetDir);
    }

    public function prepare()
    {
        if( ! file_exists($this->buildDir) )
            mkdir( $this->buildDir, 0755, true );

        if( ! file_exists($this->buildPrefix) )
            mkdir( $this->buildPrefix, 0755, true );
    }

    public function clean()
    {
        /**
         * xxx: 
         * PHP_AUTOCONF=autoconf213 ./buildconf --force
         */
        if( file_exists('Makefile') ) {
            $this->logger->info('===> Cleaning...');
            system('make clean > /dev/null') !== false 
                or die('make clean error');
        }
    }

    public function configure()
    {
        if( ! file_exists('configure') )
            system('./buildconf') !== false or die('buildconf error');

        // build configure args
        // XXX: support variants
        $args = array();
        putenv('CFLAGS=-O3');
        $args[] = './configure';

        $args[] = "--prefix=" . $this->buildPrefix;
        $args[] = "--with-config-file-path={$this->buildPrefix}/etc";
        $args[] = "--with-config-file-scan-dir={$this->buildPrefix}/var/db";
        $args[] = "--with-pear={$this->buildPrefix}/lib/php";

        $variants = new \PhpBrew\Variants();

        // XXX: detect include prefix
        $args[] = "--disable-all";
        $args = array_merge( $args , $variants->getOptions($this->version) );


        $this->logger->info("===> Configuring {$this->version}...");
        $command = join(' ', array_map( function($val) { return escapeshellarg($val); }, $args) );

        $this->logger->debug( $command );

        if( $this->options->nice )
            $command = 'nice -n ' . $this->options->nice->value . ' ' . $command;

        system( $command . ' > /dev/null' ) !== false or die('Configure failed.');
    }

    public function build()
    {

    }

    public function test()
    {

    }

    public function install()
    {

    }

}



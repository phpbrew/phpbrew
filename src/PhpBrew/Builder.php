<?php
namespace PhpBrew;
use Exception;
use PhpBrew\Config;
use PhpBrew\Variants;
use PhpBrew\CommandBuilder;
use PhpBrew\Utils;

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

    public $variants;

    public function __construct($targetDir,$version)
    {
        $this->targetDir = $targetDir;
        $this->root = Config::getPhpbrewRoot();
        $this->buildDir = Config::getBuildDir();
        $this->buildPrefix = Config::getVersionBuildPrefix( $version );
        $this->version = $version;
        $this->variants = new Variants;
        $this->variants->version = $version;
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

    public function addVariant($variant)
    {
        if( ($p = strpos( $variant , '=' )) !== false )  {
            $n = substr( $variant , 0 , $p );
            $v = substr( $variant , $p + 1 );
            $this->variants->useFeature( $n , $v );
        }
        else {
            $this->variants->useFeature( $variant );
        }
    }


    public function configure()
    {
        if( ! file_exists('configure') )
            system('./buildconf') !== false or die('buildconf error');

        // build configure args
        // XXX: support variants

        $cmd = new CommandBuilder('./configure');

        putenv('CFLAGS=-O3');
        $args[] = "--prefix=" . $this->buildPrefix;
        $args[] = "--with-config-file-path={$this->buildPrefix}/etc";
        $args[] = "--with-config-file-scan-dir={$this->buildPrefix}/var/db";
        $args[] = "--with-pear={$this->buildPrefix}/lib/php";

        $args = $this->variants->build();

        // let's apply patch for libphp{php version}.so
        if( $this->variants->isUsing('apxs2') ) {
            $this->logger->info('   -> Applying patch - apxs2 module version name ...');
            $patch=<<<'EOS'
perl -i.bak -pe 's#
libphp\$\(PHP_MAJOR_VERSION\)\.#libphp\$\(PHP_VERSION\)\.#gx' configure Makefile.global
EOS;
            Utils::system( $patch );

            $patch=<<<'EOS'
perl -i.bak -pe 's#
libs/libphp\$PHP_MAJOR_VERSION\.
#libs/libphp\$PHP_VERSION\.#gx' configure Makefile.global
EOS;
            Utils::system( $patch );
        }


        $cmd->args($args);
        $this->logger->info("===> Configuring {$this->version}...");

        $this->logger->debug( $cmd->getCommand() );

        if( $this->options->nice )
            $cmd->nice( $this->options->nice->value );
        $cmd->stdout = '/dev/null';
        $cmd->execute()  !== false or die('Configure failed.');
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



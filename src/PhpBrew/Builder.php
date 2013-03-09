<?php
namespace PhpBrew;
use Exception;
use PhpBrew\Config;
use PhpBrew\CommandBuilder;
use PhpBrew\Utils;
use PhpBrew\VariantBuilder;

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
     * @var string phpbrew root
     */
    public $root;

    public function __construct($targetDir,$version)
    {
        $this->targetDir   = $targetDir;
        $this->root        = Config::getPhpbrewRoot();
        $this->buildDir    = Config::getBuildDir();
        $this->version = $version;
        chdir($targetDir);
    }

    public function configure(\PhpBrew\Build $build)
    {
        $variantBuilder = new VariantBuilder;

        $extra = $build->getExtraOptions();

        if( ! file_exists('configure') ) {
            $this->logger->debug("configure file not found, running buildconf script...");
            system('./buildconf') !== false or die('buildconf error');
        }

        // build configure args
        // XXX: support variants

        $cmd = new CommandBuilder('./configure');

        putenv('CFLAGS=-O3');
        $prefix = $build->getInstallDirectory();
        $args[] = "--prefix=" . $prefix;
        $args[] = "--with-config-file-path={$prefix}/etc";
        $args[] = "--with-config-file-scan-dir={$prefix}/var/db";
        $args[] = "--with-pear={$prefix}/lib/php";


        $variantOptions = $variantBuilder->build($build);
        if( $variantOptions )
            $args = array_merge( $args , $variantOptions );
        
        $this->logger->debug('Enabled variants: ' . join(', ',array_keys($build->getVariants())  ));
        $this->logger->debug('Disabled variants: ' . join(', ',array_keys($build->getDisabledVariants())  ));


        if( $patchFile = $this->options->patch ) {
            // copy patch file to here
            $this->logger->info("===> Applying patch file from $patchFile ...");
            system("patch -p0 < $patchFile");
        }


        // let's apply patch for libphp{php version}.so (apxs)
        if( $build->isEnabledVariant('apxs2') ) {
            $this->logger->info('===> Applying patch - apxs2 module version name ...');

            // patch for libphp$(PHP_MAJOR_VERSION).so
            $patch=<<<'EOS'
perl -i.bak -pe 's#
libphp\$\(PHP_MAJOR_VERSION\)\.#libphp\$\(PHP_VERSION\)\.#gx' configure Makefile.global
EOS;
            Utils::system( $patch ) !== false or die('apxs2 patch failed.');


            $patch=<<<'EOS'
perl -i.bak -pe 's#
libs/libphp\$PHP_MAJOR_VERSION\.
#libs/libphp\$PHP_VERSION\.#gx' configure Makefile.global
EOS;
            Utils::system( $patch ) !== false or die('apxs2 patch failed.');


            // replace .so files
            $patch=<<<'EOS'
perl -i.bak -pe 's#
libs/libphp5.so
#libs/libphp\$PHP_VERSION\.so#gx' configure Makefile.global
EOS;
            Utils::system( $patch ) !== false or die('apxs2 patch failed.');


            // patch for OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
            // libphp$(PHP_VERSION).la:
            // replace .la files
            $patch=<<<'EOS'
perl -i.bak -pe 's#
libs/libphp5.la
#libs/libphp\$PHP_VERSION\.la#gx' configure Makefile.global
EOS;
            Utils::system( $patch ) !== false or die('apxs2 patch failed.');


            $patch=<<<'EOS'
perl -i.bak -pe 's#
libphp\$PHP_MAJOR_VERSION\.#libphp\$PHP_VERSION\.#gx' configure Makefile.global
EOS;
            Utils::system( $patch ) !== false or die('apxs2 patch failed.');



        }

        foreach( $extra as $a ) {
            $args[] = $a;
        }

        $cmd->args($args);

        $this->logger->info("===> Configuring {$build->version}...");

        $cmd->append = false;
        $cmd->stdout = Config::getVersionBuildLogPath( $build->name );

        echo "\n\n";
        echo "Use tail command to see what's going on:\n";
        echo "   $ tail -f {$cmd->stdout}\n\n\n";

        $this->logger->debug( $cmd->getCommand() );

        if( $this->options->nice )
            $cmd->nice( $this->options->nice );

        $cmd->execute() !== false or die('Configure failed.');

        // Then patch Makefile for PHP 5.3.x on 64bit system.
        if( Utils::support_64bit() && $build->compareVersion('5.4') == -1 ) {
            $this->logger->info("===> Applying patch file for php5.3.x on 64bit machine.");
            system('sed -i \'/^BUILD_/ s/\$(CC)/\$(CXX)/g\' Makefile');
            system('sed -i \'/EXTRA_LIBS = /s|$| -lstdc++|\' Makefile');
        }
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



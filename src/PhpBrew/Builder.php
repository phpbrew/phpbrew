<?php
namespace PhpBrew;
use PhpBrew\Build;

class Builder
{
    /**
     * @var \CLIFramework\Logger logger object
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
     * @var string source build directory
     */
    public $buildDir;

    /**
     * @var string phpbrew root
     */
    public $root;

    public function __construct($targetDir, $version)
    {
        $this->targetDir = $targetDir;
        $this->root      = Config::getPhpbrewRoot();
        $this->buildDir  = Config::getBuildDir();
        $this->version   = $version;
        chdir($targetDir);
    }

    public function configure(Build $build)
    {
        $variantBuilder = new VariantBuilder;

        $extra = $build->getExtraOptions();

        if (!file_exists('configure')) {
            $this->logger->debug("configure file not found, running buildconf script...");
            system('./buildconf') !== 0 or die('buildconf error');
        }

        // build configure args
        // XXX: support variants

        $cmd = new CommandBuilder('./configure');

        // putenv('CFLAGS=-O3');
        $prefix = $build->getInstallPrefix();
        $args[] = "--prefix=" . $prefix;
        $args[] = "--with-config-file-path={$prefix}/etc";
        $args[] = "--with-config-file-scan-dir={$prefix}/var/db";
        $args[] = "--with-pear={$prefix}/lib/php";

        // this is to support pear
        $build->enableVariant('xml');

        $variantOptions = $variantBuilder->build($build);

        if ($variantOptions) {
            $args = array_merge($args, $variantOptions);
        }

        $this->logger->debug('Enabled variants: ' . join(', ', array_keys($build->getVariants())));
        $this->logger->debug('Disabled variants: ' . join(', ', array_keys($build->getDisabledVariants())));

        if ($patchFiles = $this->options->patch) {
            foreach ($patchFiles as $patchFile) {
                // copy patch file to here
                $this->logger->info("===> Applying patch file from $patchFile ...");
                system("patch -p0 < $patchFile");
            }
        }

        // let's apply patch for libphp{php version}.so (apxs)
        if ($build->isEnabledVariant('apxs2')) {
            $apxs2Checker = new \PhpBrew\Tasks\Apxs2CheckTask($this->logger);
            $apxs2Checker->check($build);
            $apxs2Patch = new \PhpBrew\Tasks\Apxs2PatchTask($this->logger);

            $apxs2Patch->patch($build, $this->options);
        }

        foreach ($extra as $a) {
            $args[] = $a;
        }

        $cmd->args($args);

        $this->logger->info("===> Configuring {$build->version}...");

        $cmd->append = false;
        $cmd->stdout = $build->getBuildLogPath();

        echo "\n\n";
        echo "Use tail command to see what's going on:\n";
        echo "   $ tail -f {$cmd->stdout}\n\n\n";

        $this->logger->debug($cmd->getCommand());

        if ($this->options->nice) {
            $cmd->nice($this->options->nice);
        }

        $code = $cmd->execute();
        if ($code != 0) 
            die('Configure failed.');

        // Then patch Makefile for PHP 5.3.x on 64bit system.
        $currentVersion = preg_replace('/[^\d]*(\d+).(\d+).*/i', '$1.$2', $this->version);

        if (Utils::support64bit() && version_compare($currentVersion, '5.3', '==')) {
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

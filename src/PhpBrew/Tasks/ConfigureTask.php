<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;
use PhpBrew\Build;
use PhpBrew\VariantBuilder;

/**
 * Task to run `make`
 */
class ConfigureTask extends BaseTask
{

    public $o;

    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function setOptimizationLevel($o)
    {
        $this->o = $o;
    }

    public function configure(Build $build, $options)
    {
        $root        = Config::getPhpbrewRoot();
        $buildDir    = Config::getBuildDir();

        $variantBuilder = new VariantBuilder;
        $extra = $build->getExtraOptions();

        if ( ! file_exists('configure') ) {
            $this->debug("configure file not found, running buildconf script...");
            system('./buildconf') !== false or die('buildconf error');
        }

        $prefix = $build->getInstallPrefix();

        // append cflags
        if ($this->o) {
            $o = $this->o;
            $cflags = getenv('CFLAGS');
            putenv("CFLAGS=$cflags -O$o");
            $_ENV['CFLAGS'] = "$cflags -O$o";
        }

        $args = array();
        $args[] = "--prefix=" . $prefix;
        $args[] = "--with-config-file-path={$prefix}/etc";
        $args[] = "--with-config-file-scan-dir={$prefix}/var/db";
        $args[] = "--with-pear={$prefix}/lib/php";

        $variantOptions = $variantBuilder->build($build);
        if ($variantOptions) {
            $args = array_merge( $args , $variantOptions );
        }

        $this->debug('Enabled variants: ' . join(', ',array_keys($build->getVariants())  ));
        $this->debug('Disabled variants: ' . join(', ',array_keys($build->getDisabledVariants())  ));

        if ($patchFile = $options->patch) {
            // copy patch file to here
            $this->info("===> Applying patch file from $patchFile ...");
            system("patch -p0 < $patchFile");
        }

        // let's apply patch for libphp{php version}.so (apxs)
        if ( $build->isEnabledVariant('apxs2') ) {
            $apxs2Checker = new \PhpBrew\Tasks\Apxs2CheckTask($this->logger);
            $apxs2Checker->check($build, $options);

            $apxs2Patch = new \PhpBrew\Tasks\Apxs2PatchTask($this->logger);
            $apxs2Patch->patch($build, $options);
        }

        foreach ($extra as $a) {
            $args[] = $a;
        }

        $cmd = new CommandBuilder('./configure');
        $cmd->args($args);

        $this->info("===> Configuring {$build->version}...");
        $cmd->append = false;
        $cmd->stdout = Config::getVersionBuildLogPath( $build->name );

        echo "\n\n";
        echo "Use tail command to see what's going on:\n";
        echo "   $ tail -f {$cmd->stdout}\n\n\n";

        $this->debug( $cmd->getCommand() );

        if ($options->nice) {
            $cmd->nice( $options->nice );
        }

        if (! $options->dryrun) {
            $cmd->execute() !== false or die('Configure failed.');
        }

        $patch64bit = new \PhpBrew\Tasks\Patch64BitSupportTask($this->logger);
        $patch64bit->patch($build, $options);
    }
}

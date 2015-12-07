<?php
namespace PhpBrew\Tasks;
use PhpBrew\Exception\SystemCommandException;
use RuntimeException;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;
use PhpBrew\Build;

/**
 * Task to run `make`
 */
class ConfigureTask extends BaseTask
{
    public $optimizationLevel;

    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function setOptimizationLevel($optimizationLevel)
    {
        $this->optimizationLevel = $optimizationLevel;
    }

    public function run(Build $build, $variantOptions)
    {
        $extra = $build->getExtraOptions();
        if (!file_exists( $build->getSourceDirectory() . DIRECTORY_SEPARATOR . 'configure')) {
            $this->debug("configure file not found, running './buildconf --force'...");
            $lastline = system('./buildconf --force', $status);
            if ($status !== 0) {
                throw new SystemCommandException("buildconf error: $lastline", $build);
            }
        }
        $prefix = $build->getInstallPrefix();

        // append cflags
        if ($this->optimizationLevel) {
            $o = $this->optimizationLevel;
            $cflags = getenv('CFLAGS');
            putenv("CFLAGS=$cflags -O$o");
            $_ENV['CFLAGS'] = "$cflags -O$o";
        }

        $args = array();
        $args[] = "--prefix=" . $prefix;


        if ($this->options->{'user-config'}) {

            $args[] = "--with-config-file-path={$prefix}/etc";
            $args[] = "--with-config-file-scan-dir={$prefix}/var/db";

        } else {
            $args[] = "--with-config-file-path={$prefix}/etc";
            $args[] = "--with-config-file-scan-dir={$prefix}/var/db";
        }

        if ($variantOptions) {
            $args = array_merge($args, $variantOptions);
        }

        $this->debug('Enabled variants: ' . join(', ', array_keys($build->getVariants())));
        $this->debug('Disabled variants: ' . join(', ', array_keys($build->getDisabledVariants())));

        if ($build->isEnabledVariant('pear')) {
            $args[] = "--with-pear={$prefix}/lib/php";
        }

        foreach ((array) $this->options->patch as $patchPath) {
            // copy patch file to here
            $this->info("===> Applying patch file from $patchPath ...");

            // Search for strip parameter
            for ($i = 0; $i <= 16; $i++) {
                ob_start();
                system("patch -p$i --dry-run < $patchPath", $return);
                ob_end_clean();

                if ($return === 0) {
                    system("patch -p$i < $patchPath");
                    break;
                }
            }
        }

        // let's apply patch for libphp{php version}.so (apxs)
        if ($build->isEnabledVariant('apxs2')) {
            $apxs2Checker = new \PhpBrew\Tasks\Apxs2CheckTask($this->logger);
            $apxs2Checker->check($build, $this->options);

            $apxs2Patch = new \PhpBrew\Tasks\Apxs2PatchTask($this->logger);
            $apxs2Patch->patch($build, $this->options);
        }

        foreach ($extra as $a) {
            $args[] = $a;
        }

        $cmd = new CommandBuilder('./configure');
        $cmd->args($args);

        $buildLogPath = $build->getBuildLogPath();
        if (file_exists($buildLogPath)) {
            $newPath = $buildLogPath . '.' . filemtime($buildLogPath);
            $this->info("Found existing build.log, renaming it to $newPath");
            rename($buildLogPath,$newPath);
        }

        $this->info("===> Configuring {$build->version}...");
        $cmd->setAppendLog(true);
        $cmd->setLogPath($buildLogPath);
        $cmd->setStdout($this->options->{'stdout'});

        $this->logger->info("\n");
        $this->logger->info("Use tail command to see what's going on:");
        $this->logger->info("   $ tail -F $buildLogPath\n\n");

        $this->debug($cmd->getCommand());

        if ($this->options->nice) {
            $cmd->nice($this->options->nice);
        }

        if (!$this->options->dryrun) {
            $code = $cmd->execute();
            if ($code != 0) {
                throw new SystemCommandException("Configure failed: $code", $build, $buildLogPath);
            }
        }

        if (!$this->options->{'no-patch'}) {
            $patch64bit = new \PhpBrew\Tasks\Patch64BitSupportTask($this->logger, $this->options);
            if ($patch64bit->match($build)) {
                $patch64bit->patch($build);
            }
        }
        $build->setState(Build::STATE_CONFIGURE);
    }
}

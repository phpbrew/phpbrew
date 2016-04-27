<?php
namespace PhpBrew\Tasks;
use PhpBrew\Exception\SystemCommandException;
use RuntimeException;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;
use PhpBrew\Build;


use PhpBrew\Patches\IntlWith64bitPatch;
use PhpBrew\Patches\OpenSSLDSOPatch;

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

        // Options for specific versions
        // todo: extract to BuildPlan class: PHP53 BuildPlan, PHP54 BuildPlan, PHP55 BuildPlan ?
        if ($build->compareVersion('5.4') == -1) {
            // copied from https://github.com/Homebrew/homebrew-php/blob/master/Formula/php53.rb
            $args[] = "--enable-sqlite-utf8";
            $args[] = "--enable-zend-multibyte";
        } else if ($build->compareVersion('5.6') == -1) {
            // dtrace is not compatible with phpdbg: https://github.com/krakjoe/phpdbg/issues/38
            if (!$build->isEnabledVariant('phpdbg')) {
                $args[] = "--enable-dtrace";
            }
            $args[] = "--enable-zend-signals";
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

        if (!$this->options->{'stdout'}) {
            $this->logger->info("\n");
            $this->logger->info("Use tail command to see what's going on:");
            $this->logger->info("   $ tail -F $buildLogPath\n\n");
        }

        $this->debug($cmd->getCommand());

        if ($this->options->nice) {
            $cmd->nice($this->options->nice);
        }

        if (!$this->options->dryrun) {
            $code = $cmd->execute();
            if ($code !== 0) {
                throw new SystemCommandException("Configure failed: $code", $build, $buildLogPath);
            }
        }
        $build->setState(Build::STATE_CONFIGURE);
    }
}

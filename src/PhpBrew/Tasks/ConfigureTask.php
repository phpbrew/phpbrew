<?php

namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;
use PhpBrew\Exception\SystemCommandException;

/**
 * Task to run `make`.
 */
class ConfigureTask extends BaseTask
{
    public function run(Build $build, $variantOptions)
    {
        $extra = $build->getExtraOptions();
        $prefix = $build->getInstallPrefix();

        $args = array();

        if (!$this->options->{'no-config-cache'}) {
            // $args[] = "-C"; // project wise cache (--config-cache)
            $args[] = '--cache-file=' . Config::getCacheDir() . DIRECTORY_SEPARATOR . 'config.cache';
        }

        $args[] = '--prefix=' . $prefix;
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

        $this->debug('Enabled variants: [' . implode(', ', array_keys($build->getVariants())) . ']');
        $this->debug('Disabled variants: [' . implode(', ', array_keys($build->getDisabledVariants())) . ']');

        // Options for specific versions
        // todo: extract to BuildPlan class: PHP53 BuildPlan, PHP54 BuildPlan, PHP55 BuildPlan ?
        if ($build->compareVersion('5.4') == -1) {
            // copied from https://github.com/Homebrew/homebrew-php/blob/master/Formula/php53.rb
            $args[] = '--enable-sqlite-utf8';
            $args[] = '--enable-zend-multibyte';
        } elseif ($build->compareVersion('5.6') == -1) {
            $args[] = '--enable-zend-signals';
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
            rename($buildLogPath, $newPath);
        }

        $this->info("===> Configuring {$build->version}...");
        $cmd->setAppendLog(true);
        $cmd->setLogPath($buildLogPath);
        $cmd->setStdout($this->options->{'stdout'});

        if (!$this->options->{'stdout'}) {
            $this->logger->info("\n");
            $this->logger->info("Use tail command to see what's going on:");
            $this->logger->info('   $ tail -F ' . escapeshellarg($buildLogPath) . "\n\n");
        }

        $this->debug($cmd->buildCommand());

        if ($this->options->nice) {
            $cmd->nice($this->options->nice);
        }

        if (!$this->options->dryrun) {
            $code = $cmd->execute($lastline);
            if ($code !== 0) {
                throw new SystemCommandException("Configure failed: $lastline", $build, $buildLogPath);
            }
        }
        $build->setState(Build::STATE_CONFIGURE);
    }
}

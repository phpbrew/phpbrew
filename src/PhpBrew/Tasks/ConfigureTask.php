<?php

namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\CommandBuilder;
use PhpBrew\ConfigureParameters;
use PhpBrew\Exception\SystemCommandException;

/**
 * Task to run `make`.
 */
class ConfigureTask extends BaseTask
{
    public function run(Build $build, ConfigureParameters $parameters)
    {
        $this->debug('Enabled variants: [' . implode(', ', array_keys($build->getEnabledVariants())) . ']');
        $this->debug('Disabled variants: [' . implode(', ', array_keys($build->getDisabledVariants())) . ']');

        $cmd = new CommandBuilder('./configure');
        $cmd->args($this->renderOptions($parameters));

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
            $this->logger->info(PHP_EOL);
            $this->logger->info("Use tail command to see what's going on:");
            $this->logger->info('   $ tail -F ' . escapeshellarg($buildLogPath) . PHP_EOL . PHP_EOL);
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

    private function renderOptions(ConfigureParameters $parameters)
    {
        $args = array();

        foreach ($parameters->getOptions() as $option => $value) {
            $arg = $option;

            if ($value !== null) {
                $arg .= '=' . $value;
            }

            $args[] = $arg;
        }

        $pkgConfigPaths = $parameters->getPkgConfigPaths();

        if (count($pkgConfigPaths) > 0) {
            $args[] = 'PKG_CONFIG_PATH=' . implode(PATH_SEPARATOR, $pkgConfigPaths);
        }

        return $args;
    }
}

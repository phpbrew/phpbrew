<?php

namespace PHPBrew\Console\Command;

use PHPBrew\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvCommand extends Command
{
    protected static $defaultName = 'env';

    protected function configure()
    {
        $this
            ->setDescription('Export environment variables')
            ->addArgument('build')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $buildName = $input->getArgument('build') ?? getenv('PHPBREW_PHP');

        $this->export($output, 'PHPBREW_ROOT', Config::getRoot());
        $this->export($output, 'PHPBREW_HOME', Config::getHome());

        $this->replicate($output, 'PHPBREW_LOOKUP_PREFIX');

        if ($buildName !== false) {
            $targetPhpBinPath = Config::getVersionBinPath($buildName);

            // checking php version existence
            if (is_dir($targetPhpBinPath)) {
                $this->export($output, 'PHPBREW_PHP', $buildName);
                $this->export($output, 'PHPBREW_PATH', $targetPhpBinPath);
            }
        }

        $this->replicate($output, 'PHPBREW_SYSTEM_PHP');

        $output->writeln('# Run this command to configure your shell:');
        $output->writeln('# eval "$(phpbrew env)"');

        return 0;
    }

    private function export(OutputInterface $output, $varName, $value)
    {
        $output->writeln(sprintf('export %s=%s', $varName, $value));
    }

    private function replicate(OutputInterface $output, $varName)
    {
        $value = getenv($varName);

        if ($value !== false && $value !== '') {
            $this->export($output, $varName, $value);
        }
    }
}

<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('env')
            ->setDescription('Export environment variables.')
            ->setDefinition(array(
                new InputArgument('version', InputArgument::OPTIONAL, 'The php version to download'),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');

        // get current version
        if( ! $version )
            $version = getenv('PHPBREW_PHP');

        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();
        $buildDir = Config::getBuildDir();

        // $versionBuildPrefix = Config::getVersionBuildPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        $output->writeln('export PHPBREW_ROOT=' . $root);
        $output->writeln('export PHPBREW_HOME=' . $home);

        if ($version !== false) {
            // checking php version exists
            $targetPhpBinPath = Config::getVersionBinPath($version);
            if (!is_dir($targetPhpBinPath)) {
                throw new Exception("# php version: " . $version . " not exists.");
            }

            $output->writeln('export PHPBREW_PHP='  . $version);
            $output->writeln('export PHPBREW_PATH=' . ($version ? Config::getVersionBinPath($version) : ''));
        }

    }

}

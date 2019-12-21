<?php

namespace PHPBrew\Command;

use CLIFramework\Command;
use PHPBrew\BuildFinder;
use PHPBrew\Config;
use PHPBrew\VariantParser;

class ListCommand extends Command
{
    public function brief()
    {
        return 'List installed PHPs';
    }

    public function options($opts)
    {
        $opts->add('d|dir', 'Show php directories.');
        $opts->add('v|variants', 'Show used variants.');
    }

    public function execute()
    {
        $builds = BuildFinder::findInstalledBuilds();
        $currentBuild = Config::getCurrentPhpName();

        if (empty($builds)) {
            return $this->logger->notice('Please install at least one PHP with your preferred version.');
        }

        if ($currentBuild === false or !in_array($currentBuild, $builds)) {
            $this->logger->writeln('* (system)');
        }

        foreach ($builds as $build) {
            $versionPrefix = Config::getVersionInstallPrefix($build);

            if ($currentBuild === $build) {
                $this->logger->writeln(
                    $this->formatter->format(sprintf('* %-15s', $build), 'bold')
                );
            } else {
                $this->logger->writeln(
                    $this->formatter->format(sprintf('  %-15s', $build), 'bold')
                );
            }

            if ($this->options->dir) {
                $this->logger->writeln(sprintf('    Prefix:   %s', $versionPrefix));
            }

            // TODO: use Build class to get the variants
            if ($this->options->variants && file_exists($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants')) {
                $info = unserialize(file_get_contents($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants'));
                echo '    Variants: ';
                echo wordwrap(VariantParser::revealCommandArguments($info), 75, " \\\n              ");
                echo "\n";
            }
        }
    }
}

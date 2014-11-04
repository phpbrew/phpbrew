<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\VariantParser;

class ListCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'List installed PHPs';
    }

    public function options($opts) 
    {
        $opts->add('d|dir','Show php directories.');
        $opts->add('v|variants','Show used variants.');
    }

    public function execute()
    {
        $versions = Config::getInstalledPhpVersions();
        $currentVersion = Config::getCurrentPhpName();

        if (empty($versions)) {
            return $this->logger->notice("Please install at least one PHP with your prefered version.");
        }

        if ($currentVersion === false or !in_array($currentVersion, $versions)) {
            $this->logger->writeln("* (system)");
        }

        foreach ($versions as $version) {
            $versionPrefix = Config::getVersionInstallPrefix($version);

            if ($currentVersion == $version) {
                $this->logger->writeln(
                    $this->formatter->format(sprintf('* %-15s', $version), 'strong_white') 
                );
            } else {
                $this->logger->writeln(
                    $this->formatter->format(sprintf('  %-15s', $version), 'strong_white')
                );
            }

            if ($this->options->dir) {
                $this->logger->writeln(sprintf("    Prefix:   %s", $versionPrefix));
            }

            // TODO: use Build class to get the variants
            if ($this->options->variants && file_exists($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants')) {
                $info = unserialize(file_get_contents($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants'));
                echo "    Variants: ";
                echo wordwrap(VariantParser::revealCommandArguments($info), 75, " \\\n              ");
                echo "\n";
            }
        }
    }
}

<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\VariantParser;

class ListCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'List installed PHP versions';
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

        // var_dump( $versions );
        echo "Installed versions:\n";

        if ($currentVersion === false or in_array($currentVersion, $versions) === false) {
            echo "* (system)\n";
        }

        foreach ($versions as $version) {
            $versionPrefix = Config::getVersionBuildPrefix($version);

            printf('* %-15s', $version);

            if ($this->options->dir) {
                printf("\n    %s", $versionPrefix);
            }

            if ($this->options->variants && file_exists($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants')) {
                $info = unserialize(file_get_contents($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants'));
                echo "\n    ";
                echo wordwrap(VariantParser::revealCommandArguments($info), 75, " \\\n    ");
            }

            echo "\n";
        }
    }
}

<?php
namespace PhpBrew\Command\KnownCommand;

use PhpBrew\PhpSource;

class UnstableCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'list unstable PHP versions grouped by release managers';
    }

    public function execute()
    {
        $managers = PhpSource::getReleaseManagers();
        foreach ($managers as $id => $fullName) {
            $versions = \PhpBrew\PhpSource::getReleaseManagerVersions($id);
            echo $this->formatter->format("From $fullName --{$id}:\n", 'yellow');
            $cell = 1;
            foreach ($versions as $version => $arg) {
                if (preg_match('/RC|alpha|beta/', $version)) {
                    if ($cell++ == 4 && $cell = 1) echo "\n";
                    echo str_replace('php-', '', $version), "\t";
                }
            }
            echo "\n";
        }
    }
}

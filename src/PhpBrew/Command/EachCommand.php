<?php

namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\PhpBrew;
use Exception;

class EachCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Iterate and run a given command over all php versions managed by PHPBrew.';
    }

    public function options($options)
    {
        $options->add('d|debug', 'Show debug information');
        $options->add('y|assumeyes', 'now confirmation');
    }

    public function execute($command)
    {
        $command = trim($command);

        if (empty($command)) {
            throw new \Exception('Empty command supplied.');
        }
        $this->prompt($command);
        $this->runCommandRecursive($command);
    }

    protected function prompt($command)
    {
        if ($this->options->assumeyes) {
            return;
        }
        $versions = $this->getVersions();
        $versionlist = implode(', ', $versions);
        echo $this->formatter->format('Run ', 'transparent');
        echo $this->formatter->format($command, 'yellow');
        echo $this->formatter->format(' for php-', 'transparent');
        echo $this->formatter->format("[{$versionlist}]", 'green');
        echo $this->formatter->format('. Proceed? [', 'transparent');
        echo $this->formatter->format('Yes/no', 'green');
        echo $this->formatter->format(']> ', 'transparent');
        $response = fgetc(fopen('php://stdin', 'r'));
        if (preg_match('/n|o/i', $response)) {
            throw new \Exception('Aborted.');
        }
    }

    protected function runCommandRecursive($command)
    {
        $phpbrew = new PhpBrew();
        foreach ($this->getVersions() as $version) {
            $this->logger->info($this->formatter->format("Running `{$command}` for php-{$version}:", 'bold'));
            $output = trim($phpbrew->run(preg_split('#\s+#', $command), $version, true));
            if (!empty($output)) {
                $this->logger->info($output);
            }
        }
    }

    protected function getVersions()
    {
        $versions = Config::getInstalledPhpVersions();

        return array_map(function ($version) {
            return str_replace('php-', '', $version);
        }, $versions);
    }
}

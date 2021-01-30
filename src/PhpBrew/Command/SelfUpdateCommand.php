<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use Exception;
use GetOptionKit\OptionCollection;
use PhpBrew\Downloader\DownloadFactory;
use RuntimeException;

class SelfUpdateCommand extends Command
{
    public function usage()
    {
        return 'phpbrew self-update [branch-name]';
    }

    public function brief()
    {
        return 'Self-update, default to master version';
    }

    /**
     * @param OptionCollection $opts
     */
    public function options($opts)
    {
        DownloadFactory::addOptionsForCommand($opts);
    }

    public function execute()
    {
        global $argv;
        $script = realpath($argv[0]);

        if (!is_writable($script)) {
            throw new Exception("$script is not writable.");
        }

        // fetch new version phpbrew
        $this->logger->info("Updating phpbrew $script...");
        $url = 'https://github.com/phpbrew/phpbrew/releases/latest/download/phpbrew.phar';

        //download to a tmp file first
        //the phar file is large so we prefer the commands rather than extensions.
        $downloader = DownloadFactory::getInstance(
            $this->logger,
            $this->options,
            array(DownloadFactory::METHOD_CURL, DownloadFactory::METHOD_WGET)
        );
        $tempFile = $downloader->download($url);

        if ($tempFile === false) {
            throw new RuntimeException('Update Failed', 1);
        }
        chmod($tempFile, 0755);
        //todo we can check the hash here in order to make sure we have download the phar successfully

        if (!$this->checkRequirements($tempFile)) {
            unlink($tempFile);

            throw new RuntimeException('Update failed');
        }

        //move the tmp file to executable path
        if (!rename($tempFile, $script)) {
            throw new RuntimeException('Update Failed', 3);
        }

        $this->logger->info('Version updated.');
        system($script . ' init');
    }

    /**
     * Check if the new version is compatible with the current runtime.
     *
     * This assumes that the binary will check the runtime requirements for any sub-command including `--version`.
     *
     * @param string $binary The path to the new PHPBrew version binary
     *
     * @return bool
     */
    private function checkRequirements($binary)
    {
        system(escapeshellcmd($binary) . ' --version', $code);

        return $code === 0;
    }
}

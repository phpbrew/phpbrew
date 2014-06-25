<?php
namespace PhpBrew\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\PhpSource;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\DirectorySwitch;
use CLIFramework\Command;

class DownloadCommand extends Command
{
    /**
     * @var \CLIFramework\Logger
     */
    protected $logger = null;

    public function brief()
    {
        return 'download php';
    }

    public function usage()
    {
        return 'phpbrew download [php-version]';
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('f|force', 'Force extraction');
        $opts->add('old', 'enable old phps (less than 5.3)');
    }

    public function execute($version)
    {
        if (!preg_match('/^php-/', $version)) {
            $version = 'php-' . $version;
        }

        $info = PhpSource::getVersionInfo($version, $this->options->old);

        if (!$info) {
            throw new Exception("Version $version not found.");
        }

        $prepare = new PrepareDirectoryTask($this->logger);
        $prepare->prepareForVersion($version);

        $buildDir = Config::getBuildDir();

        $dw = new DirectorySwitch;
        $dw->cd($buildDir);

        $download = new DownloadTask($this->logger);
        $targetDir = $download->downloadByVersionString($version, $this->options->old, $this->options->force);

        if (!file_exists($targetDir)) {
            throw new Exception("Download failed.");
        }

        $this->logger->info("Done, please look at: $buildDir/$targetDir");
        $dw->back();
    }
}

<?php
namespace PhpBrew\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\PhpSource;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\DirectorySwitch;
use PhpBrew\Utils;
use CLIFramework\Command;

class DownloadCommand extends Command
{
    public function brief()
    {
        return 'download php';
    }

    public function usage()
    {
        return 'phpbrew download [php-version]';
    }

    public function arguments($args) {
        $args->add('php version')
            ->validValues(array('5.3','5.4','5.5'))
            ;
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
        $version = Utils::canonicalizeVersionName($version); // Get version name in php-{version} form
        $versionInfo = PhpSource::getVersionInfo($version, $this->options->old);
        if (!$versionInfo) {
            throw new Exception("Version $version not found.");
        }

        $prepare = new PrepareDirectoryTask($this->logger);
        $prepare->prepareForVersion($version);

        $buildDir = Config::getBuildDir();

        $download = new DownloadTask($this->logger);
        $targetDir = $download->download($versionInfo['url'], $buildDir, $this->options);

        if (!file_exists($targetDir)) {
            throw new Exception("Download failed.");
        }
        $this->logger->info("Done, please look at: $buildDir/$targetDir");
    }
}

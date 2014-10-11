<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\DirectorySwitch;
use PhpBrew\ReleaseList;
use PhpBrew\Utils;
use CLIFramework\Command;

class DownloadCommand extends Command
{
    public function brief()
    {
        return 'Download php';
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

        $version = preg_replace('/^php-/', '', $version);
        $releaseList = ReleaseList::getReadyInstance();
        $releases = $releaseList->getReleases();
        $versionInfo = $releaseList->getVersion($version);
        if (!$versionInfo) {
            throw new Exception("Version $version not found.");
        }
        $version = $versionInfo['version'];
        $distUrl = 'http://www.php.net/get/' . $versionInfo['filename'] . '/from/this/mirror';


        $prepare = new PrepareDirectoryTask($this->logger);
        $prepare->prepareForVersion($version);

        $distFileDir = Config::getDistFileDir();

        $download = new DownloadTask($this->logger);
        $targetDir = $download->download($distUrl, $distFileDir, $this->options);

        if (!file_exists($targetDir)) {
            throw new Exception("Download failed.");
        }
        $this->logger->info("Done, please look at: $targetDir");
    }
}

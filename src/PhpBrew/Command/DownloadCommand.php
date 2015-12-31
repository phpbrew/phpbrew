<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\ReleaseList;
use CLIFramework\Command;
use CLIFramework\ValueCollection;

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
        $args->add('version')->suggestions(function() {
            $releaseList = ReleaseList::getReadyInstance();
            $releases = $releaseList->getReleases();

            $collection = new ValueCollection;
            foreach($releases as $major => $versions) {
                $collection->group($major, "PHP $major", array_keys($versions));
            }

            $collection->group('pseudo', 'pseudo', array('latest', 'next'));

            return $collection;
        });
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('f|force', 'Force extraction');
        $opts->add('old', 'enable old phps (less than 5.3)');
        $opts->add('mirror:', 'Use mirror specific site.');

        DownloadFactory::addOptionsForCommand($opts);
    }

    public function execute($version)
    {

        $version = preg_replace('/^php-/', '', $version);
        $releaseList = ReleaseList::getReadyInstance($this->options);
        $releases = $releaseList->getReleases();
        $versionInfo = $releaseList->getVersion($version);
        if (!$versionInfo) {
            throw new Exception("Version $version not found.");
        }
        $version = $versionInfo['version'];
        $distUrl = 'http://www.php.net/get/' . $versionInfo['filename'] . '/from/this/mirror';
        if ($mirrorSite = $this->options->mirror) {
            // http://tw1.php.net/distributions/php-5.3.29.tar.bz2
            $distUrl = $mirrorSite . '/distributions/' . $versionInfo['filename'];
        }


        $prepare = new PrepareDirectoryTask($this->logger, $this->options);
        $prepare->run();

        $distFileDir = Config::getDistFileDir();

        $download = new DownloadTask($this->logger, $this->options);
        $targetDir = $download->download($distUrl, $distFileDir, $versionInfo['md5']);

        if (!file_exists($targetDir)) {
            throw new Exception("Download failed.");
        }
        $this->logger->info("Done, please look at: $targetDir");
    }
}

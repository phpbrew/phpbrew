<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use CLIFramework\ValueCollection;
use Exception;
use GetOptionKit\OptionSpecCollection;
use PhpBrew\Config;
use PhpBrew\Distribution\DistributionUrlPolicy;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\ReleaseList;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\PrepareDirectoryTask;

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

    public function arguments($args)
    {
        $args->add('version')->suggestions(function () {
            $releaseList = ReleaseList::getReadyInstance();
            $releases = $releaseList->getReleases();

            $collection = new ValueCollection();
            foreach ($releases as $major => $versions) {
                $collection->group($major, "PHP $major", array_keys($versions));
            }

            $collection->group('pseudo', 'pseudo', array('latest', 'next'));

            return $collection;
        });
    }

    /**
     * @param OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('f|force', 'Force extraction');
        $opts->add('old', 'enable old phps (less than 5.3)');
        $opts->add('mirror:', '[deprecated] Use mirror specific site.');

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
        $distUrlPolicy = new DistributionUrlPolicy();
        if ($this->options->mirror) {
            $this->logger->warn(
                'php.net has retired the mirror program, '
                . 'hence --mirror option has been deprecated and will be removed in the future.'
            );
        }
        $distUrl = $distUrlPolicy->buildUrl($version, $versionInfo['filename'], $versionInfo['museum']);

        $prepare = new PrepareDirectoryTask($this->logger, $this->options);
        $prepare->run();

        $distFileDir = Config::getDistFileDir();

        $download = new DownloadTask($this->logger, $this->options);
        $algo = 'md5';
        $hash = null;
        if (isset($versionInfo['sha256'])) {
            $algo = 'sha256';
            $hash = $versionInfo['sha256'];
        } elseif (isset($versionInfo['md5'])) {
            $algo = 'md5';
            $hash = $versionInfo['md5'];
        }
        $targetDir = $download->download($distUrl, $distFileDir, $algo, $hash);

        if (!file_exists($targetDir)) {
            throw new Exception('Download failed.');
        }
        $this->logger->info("Done, please look at: $targetDir");
    }
}

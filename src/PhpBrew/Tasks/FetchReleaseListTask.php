<?php
namespace PhpBrew\Tasks;
use CurlKit\CurlDownloader;
use CurlKit\ReleaseList;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Config;
use Exception;

class FetchReleaseListTask extends BaseTask
{
    public function fetch($branch = 'master') {
        $this->logger->info('===> Fetching release list...');
        $downloader = new CurlDownloader;
        $downloader->setProgressHandler(new ProgressBar);
        $url = "https://raw.githubusercontent.com/phpbrew/phpbrew/$branch/assets/releases.json";

        $this->logger->debug("---> Fetching from: $url");
        $json = $downloader->request($url);
        $localFilepath = Config::getPHPReleaseListPath();
        if (false === file_put_contents($localFilepath, $json)) {
            throw new Exception("Can't store release json file");
        }
        $this->logger->debug('---> Release list downloaded');
        $this->logger->debug('---> Decoding JSON...');
        return json_decode($json, true);
    }

}




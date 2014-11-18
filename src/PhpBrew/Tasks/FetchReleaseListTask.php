<?php
namespace PhpBrew\Tasks;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\ReleaseList;
use PhpBrew\Config;
use Exception;

class FetchReleaseListTask extends BaseTask
{
    public function fetch($branch = 'master') {
        $this->logger->info('===> Fetching release list...');
        $releaseList = new ReleaseList;
        return $releaseList->fetchRemoteReleaseList($branch, $this->options);
    }
}




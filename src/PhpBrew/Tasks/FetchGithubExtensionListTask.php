<?php
namespace PhpBrew\Tasks;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\GithubExtensionList;
use PhpBrew\ReleaseList;
use PhpBrew\Config;
use Exception;

class FetchGithubExtensionListTask extends BaseTask
{
    public function fetch($branch = 'master') {
        $this->logger->info('===> Fetching github extension list...');
        $extensionList = new GithubExtensionList;
        return $extensionList->fetchRemoteExtensionList($branch, $this->options);
    }
}




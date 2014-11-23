<?php
namespace PhpBrew\Tasks;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Extension\Provider;
use PhpBrew\ExtensionList;
use PhpBrew\ReleaseList;
use PhpBrew\Config;
use Exception;

class FetchExtensionListTask extends BaseTask
{
    public function fetch(Provider &$hosting, $branch = 'master') {

        $url = $hosting->getRemoteExtensionListUrl($branch);
        if (!is_null($url)) {
            $this->logger->info('===> Fetching '.$hosting->getName().' extension list...');
            $extensionList = new ExtensionList;
            return $extensionList->fetchRemoteExtensionList($hosting, $branch, $this->options);
        }
        return null;
    }
}




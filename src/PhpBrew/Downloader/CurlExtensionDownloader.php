<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 11:59
 */

namespace PhpBrew\Downloader;


use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Console;
use RuntimeException;

class CurlExtensionDownloader extends BaseDownloader
{

    protected function process($url, $targetFilePath)
    {
        $this->logger->info('downloading via curl extension');

        $downloader = new CurlDownloader;

        $seconds = $this->options->{'connect-timeout'};
        if ($seconds || $seconds = getenv('CONNECT_TIMEOUT')) {
            $downloader->setConnectionTimeout($seconds);
        }
        if ($proxy = $this->options->{'http-proxy'}) {
            $downloader->setProxy($proxy);
        }
        if ($proxyAuth = $this->options->{'http-proxy-auth'}) {
            $downloader->setProxyAuth($proxyAuth);
        }

        // TODO: Get current instance instead of singleton to improve testing output
        $console = Console::getInstance();
        if (! $console->options->{'no-progress'} && $this->logger->getLevel() > 2) {
            $downloader->setProgressHandler(new ProgressBar);
        }
        $binary = $downloader->request($url);
        if (false === file_put_contents($targetFilePath, $binary)) {
            throw new RuntimeException("Can't write file $targetFilePath");
        }
        return true;
    }

    public function isMethodAvailable()
    {
        return extension_loaded('curl');
    }
}
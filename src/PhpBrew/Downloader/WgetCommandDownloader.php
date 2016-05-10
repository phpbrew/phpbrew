<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 12:02
 */

namespace PhpBrew\Downloader;

use PhpBrew\Utils;

class WgetCommandDownloader extends BaseDownloader
{

    /**
     * @param string $url
     *
     * @return bool|string
     *
     * @throws \RuntimeException
     */
    protected function process($url, $targetFilePath)
    {
        $this->logger->info("Downloading $url via wget command");

        $proxy = '';
        if (!empty($this->options->{'http-proxy'})) {
            if (!empty($this->options->{'http-proxy-auth'})) {
                $proxy = sprintf('-e use_proxy=on -e http_proxy=%s', $this->options->{'http-proxy'});
            } else {
                $proxy = sprintf('-e use_proxy=on -e http_proxy=%s@%s', $this->options->{'http-proxy-auth'}, $this->options->{'http-proxy'});
            }
        }

        $quiet = $this->logger->isQuiet() ? '--quiet' : '';
        Utils::system(sprintf('wget --no-check-certificate -c %s %s -N -O %s %s', $quiet, $proxy, $targetFilePath, $url));
        return true;
    }

    public function hasSupport($requireSsl)
    {
        return Utils::findbin('wget');
    }
}

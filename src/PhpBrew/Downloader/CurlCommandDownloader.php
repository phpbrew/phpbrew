<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 11:57
 */

namespace PhpBrew\Downloader;


use PhpBrew\Utils;

class CurlCommandDownloader extends BaseDownloader
{

    /**
     * @param string $url
     *
     * @return bool|string
     *
     * @throws \RuntimeException
     */
    public function download($url, $targetFilePath)
    {
        $this->logger->info('downloading via curl command');
        //todo proxy setting
        $silent = $this->logger->isQuiet() ? '--silent ' : '';
        Utils::system("curl -C - -L $silent -o" . $targetFilePath . ' ' . $url);
    }

    public function isMethodAvailable()
    {
        return Utils::findbin('curl');
    }
}
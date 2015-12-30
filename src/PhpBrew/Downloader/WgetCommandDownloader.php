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
        $this->logger->info('downloading via wget command');
        //todo proxy setting
        $quiet = $this->logger->isQuiet() ? '--quiet' : '';
        Utils::system("wget --no-check-certificate -c $quiet -N -O \"$targetFilePath\" \"$url\"");
        return true;
    }

    public function isMethodAvailable()
    {
        return Utils::findbin('wget');
    }
}
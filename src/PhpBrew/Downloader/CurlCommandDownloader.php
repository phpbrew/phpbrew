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

    protected function process($url, $targetFilePath)
    {
        $this->logger->info('downloading via curl command');
        //todo proxy setting
        $silent = $this->logger->isQuiet() ? '--silent ' : '';
        Utils::system("curl -C - -L $silent -o" . $targetFilePath . ' "' . $url.'"');
        return true;
    }

    public function hasSupport($requireSsl)
    {
        return Utils::findbin('curl');
    }
}

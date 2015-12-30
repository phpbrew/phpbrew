<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 12:05
 */

namespace PhpBrew\Downloader;


use RuntimeException;

class FileFunctionDownloader extends BaseDownloader
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
        //todo proxy
        $binary = file_get_contents($url);
        if ($binary !== false) {
            file_put_contents($targetFilePath, $binary);
        } else {
            throw new RuntimeException("Fail to request $url");
        }
    }

    public function isMethodAvailable()
    {
        return function_exists('file_get_contents');
    }
}
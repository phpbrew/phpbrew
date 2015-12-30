<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 12:09
 */

namespace PhpBrew\Downloader;


use CLIFramework\Logger;
use GetOptionKit\OptionResult;

class Factory
{
//    private static $downloader = null;
    private static $availableDownloader = [
        CurlExtensionDownloader::class,
        FileFunctionDownloader::class,
        WgetCommandDownloader::class,
        CurlCommandDownloader::class,
    ];

    /**
     * @param Logger $logger
     * @param OptionResult $options
     * @param string $downloader
     * @return BaseDownloader
     */
    public static function getInstance($logger, $options, $downloader = null)
    {
        if (!empty($downloader)) { //auto pick download
            if (class_exists($downloader) && is_subclass_of($downloader, BaseDownloader::class)) {
                return new $downloader($logger, $options);
            }
        }
        foreach (self::$availableDownloader as $downloader) {
            $down = new $downloader($logger, $options);
            if ($down->isMethodAvailable()) {
                return $down;
            }
        }
        throw new \RuntimeException('No available downloader found!');
    }
}
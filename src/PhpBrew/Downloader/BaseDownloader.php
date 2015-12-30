<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 11:55
 */

namespace PhpBrew\Downloader;


use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Utils;

abstract class BaseDownloader
{
    public $logger;

    public $options;

    public function __construct(Logger $logger, OptionResult $options)
    {
        $this->logger = $logger;
        $this->options = $options;
    }

    /**
     * @param string $url the url to be downloaded
     * @param string $targetFilePath the path where file to be saved. null means auto-generated temp path
     *
     * @return bool|string if download successfully, return target file path, otherwise return false.
     *
     * @throws \RuntimeException
     */
    public function download($url, $targetFilePath = null)
    {
        if(empty($targetFilePath)) {
            $targetFilePath =  tempnam(sys_get_temp_dir(), 'phpbrew_');
            if($targetFilePath === false) {
                throw new RuntimeException("Fail to create temp file");
            }
        }else{
            if(!file_exists($targetFilePath)) {
                touch($targetFilePath);
            }
        }
        if(!is_writable($targetFilePath)) {
            throw new \RuntimeException("Target path ($targetFilePath) is not writable!");
        }
        if($this->process($url, $targetFilePath)){
            $this->logger->debug("$url => $targetFilePath");
            return $targetFilePath;
        }else{
            return false;
        }
    }

    protected abstract function process($url, $targetFilePath);

    /**
     *
     * @param  string $url
     * @return string|boolean the resolved download file name or false it
     *                            the url string can't be parsed
     */
    public function resolveDownloadFileName($url)
    {
        // Check if the url is for php source archive
        if (preg_match('/php-\d.+\.tar\.(bz2|gz|xz)/', $url, $parts)) {
            return $parts[0];
        }

        // try to get the filename through parse_url
        $path = parse_url($url, PHP_URL_PATH);
        if (false === $path || false === strpos($path, ".")) {
            return NULL;
        }
        return basename($path);
    }

    public abstract function isMethodAvailable();
}
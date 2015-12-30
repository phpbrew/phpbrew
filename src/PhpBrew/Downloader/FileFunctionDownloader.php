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

    protected function process($url, $targetFilePath)
    {
        $this->logger->info('downloading via pure php functions');

        $opts = array();
        if (!empty($this->options->{'http-proxy'})) {
            $opts = array(
                'http' => array(
                    'proxy' => 'tcp://127.0.0.1:8080',
                    'request_fulluri' => true,
                    'header' => array(),
                )
            );
            if($proxyAuth = $this->options->{'http-proxy-auth'}) {
                $opts['http']['header'][] = "Proxy-Authorization: Basic $proxyAuth";
            }
        }

        if(empty($opts)) {
            $binary = file_get_contents($url);
        }else{
            $context = stream_context_create($opts);
            $binary = file_get_contents($url, null, $context);
        }
        if ($binary !== false) {
            file_put_contents($targetFilePath, $binary);
            return true;
        } else {
//            throw new RuntimeException("Fail to request $url");
            return false;
        }
    }

    public function isMethodAvailable()
    {
        return function_exists('file_get_contents');
    }
}
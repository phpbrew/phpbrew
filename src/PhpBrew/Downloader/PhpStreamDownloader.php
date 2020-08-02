<?php

namespace PhpBrew\Downloader;

use RuntimeException;

class PhpStreamDownloader extends BaseDownloader
{
    protected function process($url, $targetFilePath)
    {
        $this->logger->info("Downloading $url via php stream");

        $opts = array();
        if ($proxy = $this->options->{'http-proxy'}) {
            $opts['http']['proxy'] = $proxy;
            $opts['http']['request_fulluri'] = true;
            $opts['http']['header'] = array();
            if ($proxyAuth = $this->options->{'http-proxy-auth'}) {
                $opts['http']['header'][] = "Proxy-Authorization: Basic $proxyAuth";
            }
        }
        if ($timeout = $this->options->{'connect-timeout'}) {
            $opts['http']['timeout'] = $timeout;
        }

        if ($this->options->{'continue'}) {
            $this->logger->warn('--continue is not support by this download.');
        }

        if (empty($opts)) {
            $binary = file_get_contents($url);
        } else {
            $context = stream_context_create($opts);
            $binary = file_get_contents($url, null, $context);
        }
        if ($binary === false) {
            throw new RuntimeException("Fail to request $url");
        }

        $res = file_put_contents($targetFilePath, $binary);
        if ($res === false) {
            throw new RuntimeException("Failed writing to $targetFilePath");
        }

        return true;
    }

    public function hasSupport($requireSsl)
    {
        if (!function_exists('file_get_contents')) {
            return false;
        }
        $wrappers = stream_get_wrappers();
        if ($requireSsl) {
            return in_array('https', $wrappers);
        }

        return in_array('http', $wrappers);
    }
}

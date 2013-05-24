<?php

namespace PhpBrew\Downloader;

use Guzzle\Http\Client;

class UrlDownloader
{
    public $buildDir;
    public $client;
    public $logger;

    public function __construct($logger, $buildDir)
    {
        $this->logger = $logger;
        $this->buildDir = $buildDir;

        $this->client = new Client();
    }

    /**
     * @param string $url
     * @return string downloaded file (basename)
     */
    public function download($url)
    {
        $this->logger->info("===> Downloading from $url");

        $info = parse_url($url);
        $basename = basename($info['path']);
        $targetFile = $this->buildDir.DIRECTORY_SEPARATOR.$basename;

        // curl is faster than php
        // system( 'curl -C - -# -O ' . $url ) !== false or die('Download failed.');

        // @todo implement error and progress indicator.
        $response = $this->client->get($url, null, fopen($targetFile, 'w+'))->send();

        $this->logger->info("===> $basename downloaded.");

        if (!file_exists($targetFile)) {
            throw Exception("Download failed.");
        }

        return $targetFile; // return the filename
    }

}


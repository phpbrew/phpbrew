<?php
namespace PhpBrew\Downloader;

class UrlDownloader
{
    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function download($url)
    {
        $info = parse_url($url);
        $basename = basename( $info['path'] );
        $this->logger->info("===> Downloading $url");
        system( 'curl -C - -# -O ' . $url ) !== false or die('Download failed.');
        return $basename; // return the filename
    }

}


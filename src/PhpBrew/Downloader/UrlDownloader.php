<?php
namespace PhpBrew\Downloader;

class UrlDownloader
{
    public $logger;

    function __construct($logger)
    {
        $this->logger = $logger;
    }

    function download($url)
    {
        $parts = parse_url($url);
        $basename = basename( $parts['path'] );

        $this->logger->info("===> Downloading $url");
        system( 'curl -C - -# -O ' . $url ) !== false or die('Download failed.');

        $dir = substr( $basename , 0 , strpos( $basename , '.tar.bz2' ) );

        $this->logger->info("===> Extracting...");
        system( "tar xf $basename" ) !== false or die('Extract failed.');
        return $dir; // retunr extracted path name
    }

}


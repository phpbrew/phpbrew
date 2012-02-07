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

        $this->logger->info("Downloading $url");
        system( 'curl -# -O ' . $url );

        $this->logger->info("Extracting...");
        system( "tar xzf $basename" );

        $dir = substr( $basename , 0 , strpos( $basename , '.tar.bz2' ) );
        return $dir; // retunr extracted path name
    }

}





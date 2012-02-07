<?php
namespace PhpBrew\Downloader;

class SvnDownloader
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

        $this->logger->info("Checking out from svn: $url");
        system( "svn checkout -r HEAD $url" );
        return $basename;
    }

}





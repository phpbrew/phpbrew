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


        if ( file_exists($basename) ) {
            $this->logger->info("Updating");
            system( "svn update" );
        }
        else {
            $this->logger->info("Checking out from svn: $url");
            system( "svn checkout --quiet -r HEAD $url" );
        }
        return $basename;
    }

}





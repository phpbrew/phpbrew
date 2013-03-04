<?php
namespace PhpBrew\Downloader;

class SvnDownloader
{
    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function download($url)
    {
        $parts = parse_url($url);
        $basename = basename( $parts['path'] );

        if ( file_exists($basename) ) {
            $this->logger->info("Updating");
            system( "cd $basename ; svn update" );
        }
        else {
            $this->logger->info("Checking out from svn: $url");
            system( "svn checkout --quiet -r HEAD $url" );
        }
        return $basename;
    }

}





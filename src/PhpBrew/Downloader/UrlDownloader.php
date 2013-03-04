<?php
namespace PhpBrew\Downloader;

class UrlDownloader
{
    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $url
     * @return string downloaded file (basename)
     */
    public function download($url)
    {
        $this->logger->info("===> Downloading from $url");

        $info = parse_url($url);
        $basename = basename( $info['path'] );

        // curl is faster than php
        system( 'curl -C - -# -O ' . $url ) !== false or die('Download failed.');

        $this->logger->info("===> Downloaded file $basename");

        if( ! file_exists($basename) ) {
            throw Exception("Download failed.");
        }
        return $basename; // return the filename
    }

}


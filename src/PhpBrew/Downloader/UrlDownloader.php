<?php
namespace PhpBrew\Downloader;
use RuntimeException;

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
        $ret = preg_match('/php-.+\.tar\.bz2/', $url, $parts);
        if ( false === $ret ) {
            throw new RuntimeException("Can not parse url: $url");
        }
        $basename = $parts[0];

        // check for wget or curl for downloading the php source archive
        if( exec( 'command -v wget' ) ) {
            system( 'wget -c -O ' . $basename . ' ' . $url ) !== false or die("Download failed.\n");
        } elseif (exec( 'command -v curl' )) {
            system( 'curl -C - -# -L -o ' . $basename . ' ' . $url ) !== false or die("Download failed.\n");
        } else {
            die("Download failed - neither wget nor curl was found\n");
        }

        $this->logger->info("===> $basename downloaded.");

        if( ! file_exists($basename) ) {
            throw new \Exception("Download failed.");
        }
        return $basename; // return the filename
    }

}


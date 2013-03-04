<?php
namespace PhpBrew\Downloader;

class UrlDownloader
{
    /**
     * @param string $url
     * @return string downloaded file (basename)
     */
    public function download($url)
    {
        $info = parse_url($url);
        $basename = basename( $info['path'] );
        system( 'curl -C - -# -O ' . $url ) !== false or die('Download failed.');
        return $basename; // return the filename
    }

}


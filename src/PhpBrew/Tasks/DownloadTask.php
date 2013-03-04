<?php
namespace PhpBrew\Tasks;

class DownloadTask
{

    public function downloadByVersion($targetId)
    {

    }

    public function downloadFromSvn($svn)
    {
        $downloader = new \PhpBrew\Downloader\SvnDownloader( $logger );
        $targetDir = $downloader->download( $info['svn'] );
    }

    public function downloadByUrl($url) 
    {
        $downloader = new \PhpBrew\Downloader\UrlDownloader( $logger );
        $targetDir = $downloader->download($url);
    }
}



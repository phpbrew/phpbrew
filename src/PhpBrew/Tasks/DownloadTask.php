<?php
namespace PhpBrew\Tasks;

class DownloadTask
{


    public function downloadByVersionString($version, $old = false)
    {
        $info = PhpSource::getVersionInfo($version, $old);
        $targetDir = null;
        if( isset($info['url']) ) {
            $targetDir = $this->downloadByUrl($info['url']);
        }
        elseif( isset($info['svn']) ) {
            $targetDir = $this->downloadFromSvn($info['svn']);
        }
        return $targetDir;
    }

    public function downloadFromSvn($svnUrl)
    {
        $downloader = new \PhpBrew\Downloader\SvnDownloader( $this->getLogger() );
        $targetDir = $downloader->download($svnUrl);
        return $targetDir;
    }

    public function downloadByUrl($url) 
    {
        $this->info->info("===> Downloading $url");

        $downloader = new \PhpBrew\Downloader\UrlDownloader();
        $basename = $downloader->download($url);

        // unpack the tarball file
        $targetDir = basename($basename, '.tar.bz2');

        // if we need to extract again (?)
        if( ! file_exists($dir . DIRECTORY_SEPARATOR . 'configure') ) {
            $this->logger->info("===> Extracting...");
            system( "tar xjf $basename" ) !== false or die('Extract failed.');
        }
        return $targetDir;
    }

}



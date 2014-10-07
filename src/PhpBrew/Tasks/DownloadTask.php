<?php
namespace PhpBrew\Tasks;

use PhpBrew\PhpSource;

/**
 * Task to download php distributions.
 */
class DownloadTask extends BaseTask
{

    public function downloadByVersionString($version, $old = false, $force = false)
    {
        $info = PhpSource::getVersionInfo($version, $old);
        $targetDir = null;

        if (isset($info['url'])) {
            $targetDir = $this->downloadByUrl($info['url'], $force);
        }
        return $targetDir;
    }

    public function downloadByUrl($url, $forceExtract = false)
    {
        $downloader = new \PhpBrew\Downloader\UrlDownloader($this->getLogger());
        $basename = $downloader->download($url);

        // unpack the tarball file
        $targetDir = basename($basename, '.tar.bz2');

        // if we need to extract again (?)
        if ($forceExtract || ! file_exists($targetDir . DIRECTORY_SEPARATOR . 'configure')) {
            $this->info("===> Extracting $basename...");
            system("tar xjf $basename") !== false or die('Extract failed.');
        } else {
            $this->info("Found existing $targetDir, Skip extracting.");
        }

        return realpath($targetDir);
    }
}

<?php
namespace PhpBrew\Tasks;

use PhpBrew\PhpSource;

/**
 * Task to download php distributions.
 */
class DownloadTask extends BaseTask
{

    public function downloadByVersionString($version, $dir, $old = false, $force = false)
    {
        $info = PhpSource::getVersionInfo($version, $old);
        if (isset($info['url'])) {
            return $this->downloadByUrl($info['url'], $dir, $force);
        }
        return NULL;
    }

    public function downloadByUrl($url, $dir, $forceExtract = false)
    {
        $downloader = new \PhpBrew\Downloader\UrlDownloader($this->getLogger());
        $targetFilePath = $downloader->download($url, $dir);

        // unpack the tarball file
        $extractedDir = $dir . DIRECTORY_SEPARATOR . basename($targetFilePath, '.tar.bz2');

        // if we need to extract again (?)
        if ($forceExtract || ! file_exists($extractedDir . DIRECTORY_SEPARATOR . 'configure')) {
            $this->info("===> Extracting $targetFilePath...");
            system("tar -C $dir -xjf $targetFilePath", $ret);
            if ($ret != 0) {
                die('Extract failed.');
            }
        } else {
            $this->info("Found existing $extractedDir, Skip extracting.");
        }
        return realpath($extractedDir);
    }
}

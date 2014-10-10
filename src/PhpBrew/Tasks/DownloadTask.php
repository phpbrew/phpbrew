<?php
namespace PhpBrew\Tasks;
use PhpBrew\PhpSource;
use GetOptionKit\OptionResult;

/**
 * Task to download php distributions.
 */
class DownloadTask extends BaseTask
{
    public function download($url, $dir, OptionResult $options)
    {




        $downloader = new \PhpBrew\Downloader\UrlDownloader($this->getLogger());
        $targetFilePath = $downloader->download($url, $dir);

        // unpack the tarball file
        $extractedDir = $dir . DIRECTORY_SEPARATOR . basename($targetFilePath, '.tar.bz2');

        // if we need to extract again (?)
        if ($options->force || ! file_exists($extractedDir . DIRECTORY_SEPARATOR . 'configure')) {
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

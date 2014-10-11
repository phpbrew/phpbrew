<?php
namespace PhpBrew\Tasks;
use GetOptionKit\OptionResult;
use PhpBrew\Downloader\UrlDownloader;
use Exception;

/**
 * Task to download php distributions.
 */
class DownloadTask extends BaseTask
{
    public function download($url, $md5, $dir)
    {
        if (!is_writable($dir)) {
            throw new Exception("Directory is not writable: $dir");
        }

        $downloader = new UrlDownloader($this->getLogger());
        $basename = $downloader->resolveDownloadFileName($url);
        if (!$basename) {
            throw new Exception("Can not parse url: $url");
        }
        $targetFilePath = $dir . DIRECTORY_SEPARATOR . $basename;

        if (file_exists($targetFilePath)) {
            $this->logger->info('Checking distribution checksum...');
            $md5a = md5_file($targetFilePath);
            if ($md5a != $md5) {
                $this->logger->warn("Checksum mismatch: $md5a != $md5");
                $this->logger->info("Re-Downloading...");
                $downloader->download($url, $targetFilePath);
            } else {
                $this->logger->info('Checksum matched: ' . $md5);
            }
        } else {
            $downloader->download($url, $targetFilePath);
        }
        return $targetFilePath;
    }
}

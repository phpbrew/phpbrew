<?php

namespace PhpBrew\Tasks;

use Exception;
use PhpBrew\Downloader\DownloadFactory;

/**
 * Task to download php distributions.
 */
class DownloadTask extends BaseTask
{
    public function download($url, $dir, $md5 = null)
    {
        if (!is_writable($dir)) {
            throw new \Exception("Directory is not writable: $dir");
        }

        $downloader = DownloadFactory::getInstance($this->logger, $this->options);
        $basename = $downloader->resolveDownloadFileName($url);
        if (!$basename) {
            throw new \Exception("Can not parse url: $url");
        }
        $targetFilePath = $dir.DIRECTORY_SEPARATOR.$basename;

        if (!$this->options->force && file_exists($targetFilePath)) {
            $this->logger->info('Checking distribution checksum...');
            $md5a = md5_file($targetFilePath);
            if ($md5 && $md5a != $md5) {
                $this->logger->warn("Checksum mismatch: $md5a != $md5");
                $this->logger->info('Re-Downloading...');
                $downloader->download($url, $targetFilePath);
            } else {
                $this->logger->info('Checksum matched: '.$md5);
            }
        } else {
            $downloader->download($url, $targetFilePath);
        }

        return $targetFilePath;
    }
}

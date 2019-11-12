<?php

namespace PhpBrew\Tasks;

use Exception;
use PhpBrew\Downloader\DownloadFactory;

/**
 * Task to download php distributions.
 */
class DownloadTask extends BaseTask
{
    public function download($url, $dir, $algo = 'md5', $hash = null)
    {
        if (!is_writable($dir)) {
            throw new Exception("Directory is not writable: $dir");
        }

        $downloader = DownloadFactory::getInstance($this->logger, $this->options);
        $basename = $downloader->resolveDownloadFileName($url);
        if (!$basename) {
            throw new Exception("Can not parse url: $url");
        }
        $targetFilePath = $dir . DIRECTORY_SEPARATOR . $basename;

        if (!$this->options->force && file_exists($targetFilePath)) {
            $this->logger->info('Checking distribution checksum...');
            $hash2 = hash_file($algo, $targetFilePath);
            if ($hash && $hash2 != $hash) {
                $this->logger->warn("Checksum mismatch: $hash2 != $hash");
                $this->logger->info('Re-Downloading...');
                $downloader->download($url, $targetFilePath);
            } else {
                $this->logger->info('Checksum matched: ' . $hash);
            }
        } else {
            $downloader->download($url, $targetFilePath);
        }

        return $targetFilePath;
    }
}

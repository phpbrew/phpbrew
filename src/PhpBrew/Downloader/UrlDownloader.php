<?php
namespace PhpBrew\Downloader;
use Exception;
use RuntimeException;
use CLIFramework\Logger;

use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
/*
return new CurlDownloader(array( 
    'progress' => new ProgressBar
));
 */


class UrlDownloader
{
    public $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $url
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function download($url, $dir, $basename = NULL)
    {
        $this->logger->info("===> Downloading from $url");

        $basename = $basename ?: $this->resolveDownloadFileName($url);
        if (!$basename) {
            throw new RuntimeException("Can not parse url: $url");
        }

        if (!is_writable($dir)) {
            throw new RuntimeException("Directory is not writable: $dir");
        }

        $targetFilePath = $dir . DIRECTORY_SEPARATOR . $basename;

        if (extension_loaded('curl')) {
            $this->logger->debug('Found curl extension.');
            $downloader = new CurlDownloader;
            if ($this->logger->level > 0) {
                $this->logger->debug('Using progress bar');
                $downloader->setProgressHandler(new ProgressBar);
                $binary = $downloader->request($url);
                if (false === file_put_contents($targetFilePath, $binary)) {
                    throw new RuntimeException("Can't write file $targetFilePath");
                }
            }
        } else {
            $this->logger->debug('Curl extension not found, fallback to wget or curl');

            // check for wget or curl for downloading the php source archive
            // TODO: use findbin
            if (exec('command -v wget')) {
                system('wget --no-check-certificate -c -O ' . $targetFilePath . ' ' . $url) !== false or die("Download failed.\n");
            } elseif (exec('command -v curl')) {
                system('curl -C - -# -L -o ' . $targetFilePath . ' ' . $url) !== false or die("Download failed.\n");
            } else {
                throw new RuntimeException("Download failed - neither wget nor curl was found");
            }
        }


        // Verify the downloaded file.
        if (!file_exists($targetFilePath)) {
            throw new RuntimeException("Download failed.");
        }
        $this->logger->info("===> $targetFilePath downloaded.");
        return $targetFilePath; // return the filename
    }

    /**
     *
     * @param  string         $url
     * @return string|boolean the resolved download file name or false it
     *                            the url string can't be parsed
     */
    protected function resolveDownloadFileName($url)
    {
        // Check if the url is for php source archive
        if (preg_match('/php-.+\.tar\.bz2/', $url, $parts)) {
            return $parts[0];
        }

        // try to get the filename through parse_url
        $path = parse_url($url, PHP_URL_PATH);
        if (false === $path || false === strpos($path, ".")) {
            return NULL;
        }
        return basename($path);
    }
}

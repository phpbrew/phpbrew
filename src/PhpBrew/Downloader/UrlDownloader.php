<?php
namespace PhpBrew\Downloader;
use Exception;
use RuntimeException;
use CLIFramework\Logger;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use GetOptionKit\OptionResult;

class UrlDownloader
{
    public $logger;

    public $options;

    public function __construct(Logger $logger, OptionResult $options)
    {
        $this->logger = $logger;
        $this->options = $options;
    }

    /**
     * @param string $url
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function download($url, $targetFilePath)
    {
        $this->logger->info("===> Downloading from $url");
        if (extension_loaded('curl')) {
            $this->logger->debug('---> Found curl extension, using CurlDownloader');
            $downloader = new CurlDownloader;

            if ($proxy = $this->options->{'http-proxy'}) {
                $downloader->setProxy($proxy);
            }
            if ($proxyAuth = $this->options->{'http-proxy-auth'}) {
                $downloader->setProxyAuth($proxyAuth);
            }

            if (! $this->options->{'no-progress'} && $this->logger->getLevel() > 2) {
                $downloader->setProgressHandler(new ProgressBar);
            }
            $binary = $downloader->request($url);
            if (false === file_put_contents($targetFilePath, $binary)) {
                throw new RuntimeException("Can't write file $targetFilePath");
            }
        } else {
            $this->logger->debug('Curl extension not found, fallback to wget or curl');

            // check for wget or curl for downloading the php source archive
            // TODO: use findbin
            if (exec('command -v wget')) {
                system('wget --no-check-certificate -c -O ' . $targetFilePath . ' ' . $url) !== false or die("Download failed.\n");
            } elseif (exec('command -v curl')) {
                system('curl -C - -L -o ' . $targetFilePath . ' ' . $url) !== false or die("Download failed.\n");
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
    public function resolveDownloadFileName($url)
    {
        // Check if the url is for php source archive
        if (preg_match('/php-.+\.tar\.(bz2|gz|xz)/', $url, $parts)) {
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

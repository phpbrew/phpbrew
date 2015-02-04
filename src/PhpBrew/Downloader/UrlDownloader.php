<?php
namespace PhpBrew\Downloader;
use PhpBrew\Console;
use PhpBrew\Utils;
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
     * @throws \RuntimeException
     */
    public function download($url, $targetFilePath)
    {
        $this->logger->info("===> Downloading from $url");
        if ($this->isCurlExtensionAvailable()) {
            $this->logger->debug('---> Found curl extension, using CurlDownloader');
            $downloader = new CurlDownloader;

            $seconds = $this->options->{'connect-timeout'};
            if ($seconds || $seconds = getenv('CONNECT_TIMEOUT')) {
                $downloader->setConnectionTimeout($seconds);
            }
            if ($proxy = $this->options->{'http-proxy'}) {
                $downloader->setProxy($proxy);
            }
            if ($proxyAuth = $this->options->{'http-proxy-auth'}) {
                $downloader->setProxyAuth($proxyAuth);
            }

            $console = Console::getInstance();
            if (! $console->options->{'no-progress'} && $this->logger->getLevel() > 2) {
                $downloader->setProgressHandler(new ProgressBar);
            }
            $binary = $downloader->request($url);
            if (false === file_put_contents($targetFilePath, $binary)) {
                throw new RuntimeException("Can't write file $targetFilePath");
            }
        } else {
            $this->logger->debug('Curl extension not found, fallback to wget or curl');

            // check for wget or curl for downloading the php source archive
            if ($this->isWgetCommandAvailable()) {
                $quiet = $this->logger->isQuiet() ? '--quiet' : '';
                Utils::system("wget --no-check-certificate -c $quiet -O" . $targetFilePath . ' ' . $url);
            } elseif ($this->isCurlCommandAvailable()) {
                $silent = $this->logger->isQuiet() ? '--silent ' : '';
                Utils::system("curl -C - -L $silent -o" . $targetFilePath . ' ' . $url);
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

    protected function isCurlExtensionAvailable()
    {
        return extension_loaded('curl');
    }

    protected function isWgetCommandAvailable()
    {
        return Utils::findbin('wget');
    }

    protected function isCurlCommandAvailable()
    {
        return Utils::findbin('curl');
    }
}

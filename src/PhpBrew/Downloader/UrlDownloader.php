<?php
namespace PhpBrew\Downloader;
use RuntimeException;

class UrlDownloader
{
    public $logger;

    public function __construct($logger)
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
    public function download($url)
    {
        $this->logger->info("===> Downloading from $url");

        $basename = $this->resolveDownloadFileName($url);
        if (false === $basename) {
            throw new RuntimeException("Can not parse url: $url");
        }

        // check for wget or curl for downloading the php source archive
        if (exec('command -v wget')) {
            $sysReturn = system('wget --no-check-certificate -c -O ' . $basename . ' ' . $url);

            if ($sysReturn === false) {
                die("Download failed.\n");
            }
        } elseif (exec('command -v curl')) {
            $sysReturn = system('curl -C - -# -L -o ' . $basename . ' ' . $url);

            if ($sysReturn === false) {
                die("Download failed.\n");
            }
        } else {
            die("Download failed - neither wget nor curl was found\n");
        }

        $this->logger->info("===> $basename downloaded.");

        if (!file_exists($basename)) {
            throw new \Exception("Download failed.");
        }

        return $basename; // return the filename
    }

    /**
     *
     * @param  string         $url
     * @return string|boolean the resolved download file name or false it
     *                            the url string can't be parsed
     */
    protected function resolveDownloadFileName($url)
    {
        // check if the url is for php source archive
        if (preg_match('/php-.+\.tar\.bz2/', $url, $parts)) {
            return $parts[0];
        }

        // try to get the filename through parse_url
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $path || false === strpos($path, ".")) {
            return false;
        }

        return basename($path);
    }
}

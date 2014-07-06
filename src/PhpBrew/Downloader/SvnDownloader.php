<?php
namespace PhpBrew\Downloader;

use Exception;

class SvnDownloader
{
    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function download($url, $target = null, $force = false)
    {
        $parts = parse_url($url);
        $basename = $target ?: basename($parts['path']);

        if (file_exists($basename . DIRECTORY_SEPARATOR . '.svn')) {
            $this->logger->info("Found existing repository, updating...");
            system("cd $basename ; svn update");
        } elseif (file_exists($basename)) {
            $path = getcwd() . DIRECTORY_SEPARATOR . $basename;
            throw new Exception("$path exists, but it's not a SVN repository.");
        } else {
            $this->logger->info("Checking out from svn: $url");
            system("svn checkout --quiet -r HEAD $url");
        }

        if (!file_exists($basename)) {
            throw new Exception("Download failed.");
        }

        return $basename;
    }
}

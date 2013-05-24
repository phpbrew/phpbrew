<?php

namespace PhpBrew\Tasks;

use CLIFramework\Logger;
use PhpBrew\PhpSource;
use PhpBrew\Config;
use PhpBrew\Downloader\SvnDownloader;
use PhpBrew\Downloader\UrlDownloader;
use Symfony\Component\Process\Process;

/**
 * Task to download php distributions.
 */
class DownloadTask extends BaseTask
{
    private $buildDir;

    public function __construct(Logger $logger, $buildDir)
    {
        $this->buildDir = $buildDir;

        parent::__construct($logger);
    }

    public function downloadByVersionString($version, $old = false, $force = false)
    {
        $info = PhpSource::getVersionInfo($version, $old);

        $targetDir = null;
        if( isset($info['url']) ) {
            $targetDir = $this->downloadByUrl($info['url'], $force);
        }
        elseif( isset($info['svn']) ) {
            $targetDir = $this->downloadFromSvn($info['svn']);
        }
        return $targetDir;
    }

    public function downloadFromSvn($svnUrl)
    {
        $downloader = new SvnDownloader( $this->getLogger() );
        $targetDir = $downloader->download($svnUrl);
        return realpath($targetDir);
    }

    public function downloadByUrl($url, $forceExtract = false )
    {
        $downloader = new UrlDownloader($this->getLogger(), $this->buildDir);
        $file = $downloader->download($url);

        // unpack the tarball file
        $targetDir = basename($file, '.tar.bz2');

        // if we need to extract again (?)
        if ($forceExtract || ! file_exists($targetDir . DIRECTORY_SEPARATOR . 'configure')) {
            $this->info("===> Extracting $file...");

            $process = new Process("tar -xjf $file");
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
        } else {
            $this->info("Found existing $targetDir, Skip extracting.");
        }

        return realpath($targetDir);
    }

}



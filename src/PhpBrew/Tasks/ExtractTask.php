<?php
namespace PhpBrew\Tasks;

use RuntimeException;
use PhpBrew\Config;
use PhpBrew\Build;

/**
 * Task to download php distributions.
 */
class ExtractTask extends BaseTask
{

    /**
     * Unpacks the source tarball file.
     *
     * @param string $targetFilePath absolute file path
     */
    public function extract(Build $build, $targetFilePath, $extractDir = NULL)
    {
        $extractDirTemp = Config::getTempFileDir();
        if (!$extractDir) {
            $extractDir = dirname($targetFilePath);
        }

        $extractedDirTemp = $extractDirTemp . DIRECTORY_SEPARATOR . preg_replace('#\.tar\.(gz|bz2)$#', '', basename($targetFilePath));
        $extractedDir     = $extractDir . DIRECTORY_SEPARATOR . $build->getName();

        if ($build->getState() >= Build::STATE_EXTRACT && file_exists($extractedDir . DIRECTORY_SEPARATOR . 'configure') ) {
            $this->info("===> Distribution file was successfully extracted, skipping...");
            return $extractedDir;
        }

        // NOTICE: Always extract to prevent incomplete extraction
        $this->info("===> Extracting $targetFilePath to $extractedDirTemp");
        system("tar -C $extractDirTemp -xjf $targetFilePath", $ret);
        if ($ret != 0) {
            throw new RuntimeException('Extract failed.');
        }

        if(!is_dir($extractedDirTemp)){
            die("Unable to find $extractedDirTemp");
        }

        if(is_dir($extractedDir)){
            $this->info("===> Removing $extractedDir");
            system("rm -rf $extractedDir", $ret);
            if ($ret != 0) {
                die("Unable to remove $extractedDir.");
            }
        }

        $this->info("===> Moving $extractedDirTemp to $extractedDir");
        if(!rename($extractedDirTemp, $extractedDir)){
            die("Unable to move $extractedDirTemp to $extractedDir");
        }

        $build->setState(Build::STATE_EXTRACT);
        return $extractedDir;
        /*
         * XXX: unless we have a fast way to verify the extraction.
        if ($this->options->force || ! file_exists($extractedDir . DIRECTORY_SEPARATOR . 'configure')) {
            $this->info("===> Extracting $targetFilePath...");
            system("tar -C $dir -xjf $targetFilePath", $ret);
            if ($ret != 0) {
                die('Extract failed.');
            }
        } else {
            $this->info("Found existing $extractedDir, Skip extracting.");
        }
        */
    }
}

<?php
namespace PhpBrew\Tasks;
use GetOptionKit\OptionResult;

/**
 * Task to download php distributions.
 */
class ExtractTask extends BaseTask
{

    /**
     * @param string $targetFilePath absolute file path
     */
    public function extract($targetFilePath)
    {
        // unpack the tarball file
        $dir = dirname($targetFilePath);
        $extractedDir = $dir . DIRECTORY_SEPARATOR . basename($targetFilePath, '.tar.bz2');


        // NOTICE: Always extract to prevent incomplete extraction
        $this->info("===> Extracting $targetFilePath...");
        system("tar -C $dir -xjf $targetFilePath", $ret);
        if ($ret != 0) {
            die('Extract failed.');
        }

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
        return $extractedDir;
    }
}

<?php
namespace PhpBrew\Tasks;
use PhpBrew\Exception\SystemCommandException;
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
     *
     * @param string $extractDir (the build dir)
     */
    public function extract(Build $build, $targetFilePath, $extractDir = NULL)
    {
        if (empty($extractDir)) {
            $extractDir = dirname($targetFilePath);
        }
        $extractDirTemp = tempnam($extractDir, 'php_');

        if (!file_exists($extractDirTemp)) {
            @mkdir($extractDirTemp, 0755, true);
        }

        // This converts: '/opt/phpbrew/distfiles/php-7.0.2.tar.bz2'
        //        to just '/opt/phpbrew/tmp/distfiles/php-7.0.2'
        $distBasename = preg_replace('#\.tar\.(gz|bz2)$#', '', basename($targetFilePath));
        $extractedDirTemp = $extractDirTemp . DIRECTORY_SEPARATOR . $distBasename;
        $extractedDir     = $extractDir . DIRECTORY_SEPARATOR . $build->getName();

        if ($build->getState() >= Build::STATE_EXTRACT && file_exists($extractedDir . DIRECTORY_SEPARATOR . 'configure') ) {
            $this->info("===> Distribution file was successfully extracted, skipping...");
            return $extractedDir;
        }

        // NOTICE: Always extract to tmp directory prevent incomplete extraction
        $this->info("===> Extracting $targetFilePath to $extractedDirTemp");
        $lastline = system("tar -C $extractDirTemp -xf $targetFilePath", $ret);
        if ($ret !== 0) {
            throw new SystemCommandException("Extract failed: $lastline", $build);
        }
        clearstatcache(true);
        if (!is_dir($extractedDirTemp)) {
            // retry with github extracted dir path
            $extractedDirTemp = $extractDirTemp . DIRECTORY_SEPARATOR . 'php-src-' . $distBasename;
            if (!is_dir($extractedDirTemp)) {
                throw new SystemCommandException("Unable to find $extractedDirTemp", $build);
            }
        }

        if (is_dir($extractedDir)) {
            $this->info("===> Removing $extractedDir");
            $lastline = system("rm -rf $extractedDir", $ret);
            if ($ret !== 0) {
                throw new SystemCommandException("Unable to remove $extractedDir: $lastline", $build);
            }
        }

        $this->info("===> Moving $extractedDirTemp to $extractedDir");
        if (!rename($extractedDirTemp, $extractedDir)) {
            throw new SystemCommandException("Unable to move $extractedDirTemp to $extractedDir", $build);
        }

        @rmdir($extractDirTemp);

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

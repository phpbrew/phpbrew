<?php

namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\Exception\SystemCommandException;

/**
 * Task to download php distributions.
 */
class ExtractTask extends BaseTask
{
    /**
     * The list of directories to be removed upon task completion.
     *
     * @var string[]
     */
    private $rmDirs = array();

    /**
     * Unpacks the source tarball file.
     *
     * @param string $targetFilePath absolute file path
     * @param string $extractDir     (the build dir)
     *
     * @return string The extracted directory path
     */
    public function extract(Build $build, $targetFilePath, $extractDir = null)
    {
        if (empty($extractDir)) {
            $extractDir = dirname($targetFilePath);
        }

        $extractedDir = $extractDir . DIRECTORY_SEPARATOR . $build->getName();

        if (
            $build->getState() >= Build::STATE_EXTRACT
            && file_exists($extractedDir . DIRECTORY_SEPARATOR . 'configure')
        ) {
            $this->info('===> Distribution file was successfully extracted, skipping...');

            return $extractedDir;
        }

        $extractDirTemp = $extractDir . DIRECTORY_SEPARATOR . 'tmp.' . time();

        if (!file_exists($extractDirTemp)) {
            mkdir($extractDirTemp, 0755, true);
        }

        $this->rmDirs[] = $extractDirTemp;

        // This converts: '/opt/phpbrew/distfiles/php-7.0.2.tar.bz2'
        //        to just '/opt/phpbrew/tmp/distfiles/php-7.0.2'
        $distBaseName = preg_replace('#\.tar\.(gz|bz2)$#', '', basename($targetFilePath));
        $extractedDirTemp = $extractDirTemp . DIRECTORY_SEPARATOR . $distBaseName;

        // NOTICE: Always extract to tmp directory prevent incomplete extraction
        $this->info("===> Extracting $targetFilePath to $extractedDirTemp");
        $lastLine = system(
            'tar -C ' . escapeshellarg($extractDirTemp) . ' -xf ' . escapeshellarg($targetFilePath),
            $ret
        );

        if ($ret !== 0) {
            throw new SystemCommandException("Extract failed: $lastLine", $build);
        }

        clearstatcache(true);

        if (!is_dir($extractedDirTemp)) {
            // retry with github extracted dir path
            $extractedDirTemp = $extractDirTemp . DIRECTORY_SEPARATOR . 'php-src-' . $distBaseName;

            if (!is_dir($extractedDirTemp)) {
                throw new SystemCommandException("Unable to find $extractedDirTemp", $build);
            }
        }

        if (is_dir($extractedDir)) {
            $this->info("===> Found existing build directory, removing $extractedDir ...");
            $lastLine = $this->rmDir($extractedDir, $ret);

            if ($ret !== 0) {
                throw new SystemCommandException("Unable to remove $extractedDir: $lastLine", $build);
            }
        }

        $this->info("===> Moving $extractedDirTemp to $extractedDir");

        if (!rename($extractedDirTemp, $extractedDir)) {
            throw new SystemCommandException("Unable to move $extractedDirTemp to $extractedDir", $build);
        }

        $build->setState(Build::STATE_EXTRACT);

        return $extractedDir;
    }

    public function __destruct()
    {
        foreach ($this->rmDirs as $dir) {
            $this->rmDir($dir);
        }

        parent::__destruct();
    }

    private function rmDir($dir, &$return = null)
    {
        return system('rm -rf ' . escapeshellarg($dir), $return);
    }
}

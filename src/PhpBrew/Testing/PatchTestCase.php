<?php
namespace PhpBrew\Testing;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PHPUnit_Framework_TestCase;

abstract class PatchTestCase extends PHPUnit_Framework_TestCase
{

    protected function setupBuildDirectory($version)
    {
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');
        $sourceFixtureDirectory = getenv('PHPBREW_FIXTURES_PHP_DIR') . DIRECTORY_SEPARATOR . $version;

        $source = $sourceFixtureDirectory;
        $dest = $sourceDirectory;

        if (!file_exists($dest)) {
            mkdir($dest, 0755, true);
        }
        $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0755, true);
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

    protected function cleanupBuildDirectory()
    {
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');
        if (!is_dir($sourceDirectory)) {
            return;
        }

        $directoryIterator = new RecursiveDirectoryIterator($sourceDirectory, RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        if (is_dir($sourceDirectory)) {
            rmdir($sourceDirectory);
        } elseif (is_file($sourceDirectory)) {
            unlink($sourceDirectory);
        }
    }

    public function setUp()
    {
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');
        $this->cleanupBuildDirectory();
        if (!file_exists($sourceDirectory)) {
            mkdir($sourceDirectory, 0755, true);
        }
    }

    public function tearDown()
    {
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        // don't clean up if the test failed.
        if ($this->hasFailed()) {
            return;
        }
        $this->cleanupBuildDirectory();
    }
}

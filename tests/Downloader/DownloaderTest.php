<?php

namespace PHPBrew\Tests\Downloader;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PHPBrew\Config;
use PHPBrew\Downloader\DownloadFactory;
use PHPBrew\Testing\VCRAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @large
 */
class DownloaderTest extends TestCase
{
    public $logger;

    public function setUp()
    {
        $this->logger = Logger::getInstance();
        $this->logger->setQuiet();

        VCRAdapter::enableVCR($this);
    }

    public function tearDown()
    {
        VCRAdapter::disableVCR();
    }

    /**
     * @group noVCR
     */
    public function testDownloadByWgetCommand()
    {
        $this->assertDownloaderWorks('PHPBrew\Downloader\WgetCommandDownloader');
    }

    /**
     * @group noVCR
     */
    public function testDownloadByCurlCommand()
    {
        $this->assertDownloaderWorks('PHPBrew\Downloader\CurlCommandDownloader');
    }

    public function testDownloadByCurlExtension()
    {
        $this->assertDownloaderWorks('PHPBrew\Downloader\PhpCurlDownloader');
    }

    public function testDownloadByFileFunction()
    {
        $this->assertDownloaderWorks('PHPBrew\Downloader\PhpStreamDownloader');
    }

    private function assertDownloaderWorks($downloader)
    {
        $instance = DownloadFactory::getInstance($this->logger, new OptionResult(), $downloader);
        if ($instance->hasSupport(false)) {
            $actualFilePath = tempnam(Config::getTempFileDir(), '');
            $instance->download('http://httpbin.org/', $actualFilePath);
            $this->assertFileExists($actualFilePath);
        } else {
            $this->markTestSkipped();
        }
    }
}

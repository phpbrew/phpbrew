<?php

namespace PhpBrew\Tests\Downloader;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Testing\VCRAdapter;
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
        $this->assertDownloaderWorks('PhpBrew\Downloader\WgetCommandDownloader');
    }

    /**
     * @group noVCR
     */
    public function testDownloadByCurlCommand()
    {
        $this->assertDownloaderWorks('PhpBrew\Downloader\CurlCommandDownloader');
    }

    public function testDownloadByCurlExtension()
    {
        $this->assertDownloaderWorks('PhpBrew\Downloader\PhpCurlDownloader');
    }

    public function testDownloadByFileFunction()
    {
        $this->assertDownloaderWorks('PhpBrew\Downloader\PhpStreamDownloader');
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

<?php
namespace PhpBrew\Downloader;

use GetOptionKit\OptionResult;
use CLIFramework\Logger;
use PhpBrew\Config;

/**
 * @large
 */
class UrlDownloaderTest extends \PHPUnit_Framework_TestCase
{
    public $logger;

    public function setUp()
    {
        $this->logger = Logger::getInstance();
        $this->logger->setQuiet();
    }

    public function testDownloadByWgetCommand()
    {
        $downloader = new UrlDownloaderForTest($this->logger, new OptionResult);
        $downloader->setIsWgetCommandAvailable(true);
        $actualFilePath = tempnam(Config::getTempFileDir(), '');

        $downloader->download('http://httpbin.org/', $actualFilePath);

        $this->assertTrue($downloader->isWgetCommandAvailable());
        $this->assertFileExists($actualFilePath);
    }

    public function testDownloadByCurlCommand()
    {
        $downloader = new UrlDownloaderForTest($this->logger, new OptionResult);
        $downloader->setIsCurlCommandAvailable(true);
        $actualFilePath = tempnam(Config::getTempFileDir(), '');

        $downloader->download('http://httpbin.org/', $actualFilePath);

        $this->assertTrue($downloader->isCurlCommandAvailable());
        $this->assertFileExists($actualFilePath);
    }
}

class UrlDownloaderForTest extends UrlDownloader
{
    private $isCurlExtensionAvailable = false;
    private $isWgetCommandAvailable = false;
    private $isCurlCommandAvailable = false;

    public function setIsCurlExtensionAvailable($value)
    {
        $this->isCurlExtensionAvailable = $value;
    }

    public function isCurlExtensionAvailable()
    {
        return $this->isCurlExtensionAvailable;
    }

    public function setIsWgetCommandAvailable($value)
    {
        $this->isWgetCommandAvailable = $value;
    }

    public function isWgetCommandAvailable()
    {
        return $this->isWgetCommandAvailable;
    }

    public function setIsCurlCommandAvailable($value)
    {
        $this->isCurlCommandAvailable = $value;
    }

    public function isCurlCommandAvailable()
    {
        return $this->isCurlCommandAvailable;
    }
}

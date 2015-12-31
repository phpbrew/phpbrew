<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 12:23
 */

namespace PhpBrew\Downloader;


use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Config;
use PHPUnit_Framework_TestCase;

/**
 * @large
 */
class DownloaderTest extends PHPUnit_Framework_TestCase
{
    public $logger;

    public function setUp()
    {
        $this->logger = Logger::getInstance();
        $this->logger->setQuiet();
    }

    public function testDownloadByWgetCommand()
    {
        $this->_test('PhpBrew\Downloader\WgetCommandDownloader');
    }

    public function testDownloadByCurlCommand()
    {
        $this->_test('PhpBrew\Downloader\CurlCommandDownloader');
    }

    public function testDownloadByCurlExtension()
    {
        $this->_test('PhpBrew\Downloader\CurlExtensionDownloader');
    }

    public function testDownloadByFileFunction()
    {
        $this->_test('PhpBrew\Downloader\FileFunctionDownloader');
    }

    private function _test($downloader)
    {
        $instance = DownloadFactory::getInstance($this->logger, new OptionResult, $downloader);
        if ($instance->isMethodAvailable()) {
            $actualFilePath = tempnam(Config::getTempFileDir(), '');
            $instance->download('http://httpbin.org/', $actualFilePath);
            $this->assertFileExists($actualFilePath);
        } else {
            $this->markTestSkipped();
        }
    }
}
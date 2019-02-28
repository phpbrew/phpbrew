<?php
/**
 * Created by PhpStorm.
 * User: xiami
 * Date: 2015/12/30
 * Time: 12:23
 */

namespace PhpBrew\Downloader;


use PhpBrew\Config;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Testing\VCRAdapter;
use \PHPUnit\Framework\TestCase;

/**
 * @large
 */
class DownloaderTest extends \PHPUnit\Framework\TestCase
{
    public $logger;

    public function setUp()
    {
        $this->logger = Logger::getInstance();
        $this->logger->setQuiet();

        VCRAdapter::enableVCR($this);
    }

    public function tearDown() {
        VCRAdapter::disableVCR();
    }

    /**
     * @group noVCR
     */
    public function testDownloadByWgetCommand()
    {
        $this->_test('PhpBrew\Downloader\WgetCommandDownloader');
    }

    /**
     * @group noVCR
     */
    public function testDownloadByCurlCommand()
    {
        $this->_test('PhpBrew\Downloader\CurlCommandDownloader');
    }

    public function testDownloadByCurlExtension()
    {
        $this->_test('PhpBrew\Downloader\PhpCurlDownloader');
    }

    public function testDownloadByFileFunction()
    {
        $this->_test('PhpBrew\Downloader\PhpStreamDownloader');
    }

    private function _test($downloader)
    {
        $instance = DownloadFactory::getInstance($this->logger, new OptionResult, $downloader);
        if ($instance->hasSupport(false)) {
            $actualFilePath = tempnam(Config::getTempFileDir(), '');
            $instance->download('http://httpbin.org/', $actualFilePath);
            $this->assertFileExists($actualFilePath);
        } else {
            $this->markTestSkipped();
        }
    }
}

<?php

class UrlDownloaderTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $logger = CLIFramework\Logger::getInstance();
        $d = new PhpBrew\Downloader\UrlDownloader( $logger );
    }

}

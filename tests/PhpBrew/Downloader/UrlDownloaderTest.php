<?php

class UrlDownloaderTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $logger = CLIFramework\Logger::getInstance();
        $d = new PhpBrew\Downloader\UrlDownloader( $logger );
        ok( $d );
    }

}


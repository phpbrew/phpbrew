<?php

namespace PHPBrew\Tests\Command;

use PHPBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @group noVCR
 */
class DownloadCommandTest extends CommandTestCase
{

    public function versionDataProvider()
    {
        return array(
            array('5.5'),
            array('5.5.15'),
        );
    }

    /**
     * @outputBuffering enabled
     * @dataProvider versionDataProvider
     */
    public function testDownloadCommand($versionName)
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped('Skip heavy test on Travis');
        }

        $this->assertCommandSuccess("phpbrew init");
        $this->assertCommandSuccess("phpbrew -q download $versionName");

        // re-download should just check the checksum instead of extracting it
        $this->assertCommandSuccess("phpbrew -q download $versionName");
        $this->assertCommandSuccess("phpbrew -q download -f $versionName");
    }
}

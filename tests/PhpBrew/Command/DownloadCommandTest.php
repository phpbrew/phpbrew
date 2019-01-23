<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
 * @group noVCR
 */
class DownloadCommandTest extends CommandTestCase
{

    public function versionDataProvider() {
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
        $this->assertCommandSuccess("phpbrew init");
        $this->assertCommandSuccess("phpbrew -q download $versionName");
        $this->assertCommandSuccess("phpbrew -q download $versionName"); // redownload should just check the checksum instead of extracting it.
        $this->assertCommandSuccess("phpbrew -q download -f $versionName");
    }
}

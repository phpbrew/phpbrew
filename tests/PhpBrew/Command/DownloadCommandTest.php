<?php
use PhpBrew\Testing\CommandTestCase;

/**
 * @large
 * @group command
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
    public function testDownloadCommand($versionName) {
        $this->assertTrue($this->runCommand("phpbrew -q download $versionName"));
        $this->assertTrue($this->runCommand("phpbrew -q download $versionName")); // redownload should just check the checksum instead of extracting it.
        $this->assertTrue($this->runCommand("phpbrew -q download -f $versionName"));
    }
}

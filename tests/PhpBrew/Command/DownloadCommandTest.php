<?php
use PhpBrew\Testing\CommandTestCase;

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
     */
    public function testDownloadCommand($versionName) {
        ob_start();
        $this->assertTrue($this->runCommand("phpbrew download $versionName"));
        $this->assertTrue($this->runCommand("phpbrew download $versionName")); // redownload should just check the checksum instead of extracting it.
        $this->assertTrue($this->runCommand("phpbrew download -f $versionName"));
        ob_end_clean();
    }


}

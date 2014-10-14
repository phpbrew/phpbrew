<?php

use CLIFramework\Testing\CommandTestCase;

class ExtCommandTest extends CommandTestCase
{
    public function setupApplication() {
        $application = new PhpBrew\Console;
        $application->getLogger()->getFormatter()->preferRawOutput();

        return $application;
    }

    public function testOutput()
    {
        ob_start();
        $this->runCommand('phpbrew ext');
        $output = ob_get_clean();

        $this->assertEquals(1, preg_match_all('#^Loaded extensions:#m', $output, $matches)); // match once
        $this->assertEquals(1, preg_match_all('#^Available extensions:#m', $output, $matches)); // match once

        $this->assertRegExp('#\[\*\]\s+\w+$#m', $output);
        $this->assertRegExp('#\[ \]\s+\w+$#m', $output);
    }

    /**
     * @outputBuffering enabled
     */
    public function testOutputWithDifferentPHPVersion()
    {
        $this->runCommand('phpbrew ext --php 0.0');
        $this->expectOutputRegex('#^PHP version is different from current active version.#');
    }
}

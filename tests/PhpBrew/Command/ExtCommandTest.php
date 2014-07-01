<?php

class ExtCommandTest extends PHPUnit_Framework_TestCase
{
    protected $application;

    protected function setUp()
    {
        $this->application = new PhpBrew\Console;
        $this->application->getLogger()->getFormatter()->preferRawOutput(); // use non colored output
    }

    public function testOutput()
    {
        ob_start();
        $this->application->run( array(null, 'ext') );
        $output = ob_get_clean();

        $this->assertEquals(1, preg_match_all('#^Loaded extensions:#m', $output, $matches)); // match once
        $this->assertEquals(1, preg_match_all('#^Available extensions:#m', $output, $matches)); // match once

        $this->markTestIncomplete("We would need a DI container and a few mock objects to be able to fully test this command.");

        $this->assertRegExp('#\[\*\]\s+\w+$#m', $output);
        $this->assertRegExp('#\[ \]\s+\w+$#m', $output);
    }

    /**
     * @outputBuffering enabled
     */
    public function testOutputWithDifferentPHPVersion()
    {
        $this->application->run( array(null, 'ext', '--php', '0.0') );

        $this->expectOutputRegex('#^PHP version is different from current active version.#');
    }
}

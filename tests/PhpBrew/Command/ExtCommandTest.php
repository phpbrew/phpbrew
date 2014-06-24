<?php

class ExtCommandTest extends PHPUnit_Framework_TestCase
{
    public function testOutput()
    {
        ob_start();
        $console = new PhpBrew\Console;
        $console->run( array(null, 'ext') );
        $output = ob_get_clean();

        $this->assertEquals(1, preg_match_all('#Loaded extensions:#', $output));
        $this->assertRegExp('#\[\*\]#', $output);
        $this->assertEquals(count(get_loaded_extensions()), preg_match_all('#\[\*\]#', $output));
        $this->assertEquals(1, preg_match_all('#Available extensions:#', $output));
        $this->assertRegExp('#\[ \]#', $output);
    }

    /**
     * @outputBuffering enabled
     */
    public function testOutputWithDifferentPHPVersion()
    {
        $console = new PhpBrew\Console;
        $console->run( array(null, 'ext', '--php', '0.0') );

        $this->expectOutputRegex('#PHP version is different from current active version.#');
    }
}

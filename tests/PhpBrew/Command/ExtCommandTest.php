<?php

class ExtCommandTest extends PHPUnit_Framework_TestCase
{
    public function testOutput()
    {
        ob_start();
        $console = new PhpBrew\Console;
        $console->run( array(null, 'ext') );

        $output = explode("\n", ob_get_clean());

        $extensions = count(get_loaded_extensions());

        $this->assertSame('', array_pop($output)); // must end with a blank line
        
        foreach ($output as $line => $buffer) {
            if($line === 0) { // title
                $this->assertContains('Loaded extensions:', $buffer);
            } else if($line <= $extensions) { // loaded extensions
                $this->assertContains('[*]', $buffer);
            } else if($line == $extensions + 1) { // title
                $this->assertContains('Available extensions:', $buffer);
            } else { // available extensions
                $this->assertContains('[ ]', $buffer);
            }
        }
    }

    public function testOutputWithDifferentPHPVersion()
    {
        ob_start();
        $console = new PhpBrew\Console;
        $console->run( array(null, 'ext', '--php', '0.0') );
        $output = ob_get_clean();

        $this->assertContains('PHP version is different from current active version.', $output);
    }
}

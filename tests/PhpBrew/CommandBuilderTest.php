<?php

/**
 * @small
 */
class CommandBuilderTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        ob_start();
        $cmd = new PhpBrew\CommandBuilder('ls');
        $this->assertEquals(0, $cmd->execute());
        ob_end_clean();
    }

    /**
     * @dataProvider provideTestGetCommandTestCases
     */
    public function testGetCommand($appendLog, $stdout, $logPath, $expected)
    {
        $cmd = new PhpBrew\CommandBuilder('ls');
        $cmd->setAppendLog($appendLog);
        $cmd->setStdout($stdout);
        $cmd->setLogPath($logPath);
        $this->assertEquals($expected, $cmd->getCommand());
        ob_start();
        $this->assertEquals(0, $cmd->execute());
        ob_end_clean();
    }

    public function provideTestGetCommandTestCases()
    {
        return array(
            array(
                'appendLog' => true,
                'stdout'    => true,
                'logPath'   => '/tmp/build.log',
                'expected'      => 'ls | tee --append /tmp/build.log 2>&1'
            ),
            array(
                'appendLog' => false,
                'stdout'    => true,
                'logPath'   => '/tmp/build.log',
                'expected'      => 'ls | tee /tmp/build.log 2>&1'
            ),
            array(
                'appendLog' => true,
                'stdout'    => false,
                'logPath'   => '/tmp/build.log',
                'expected'      => 'ls >> /tmp/build.log 2>&1'
            ),
            array(
                'appendLog' => false,
                'stdout'    => false,
                'logPath'   => '/tmp/build.log',
                'expected'      => 'ls > /tmp/build.log 2>&1'
            ),
            array(
                'appendLog' => true,
                'stdout'    => true,
                'logPath'   => null,
                'expected'      => 'ls'
            ),
            array(
                'appendLog' => false,
                'stdout'    => true,
                'logPath'   => null,
                'expected'      => 'ls'
            ),
            array(
                'appendLog' => true,
                'stdout'    => false,
                'logPath'   => null,
                'expected'      => 'ls'
            ),
            array(
                'appendLog' => false,
                'stdout'    => false,
                'logPath'   => null,
                'expected'      => 'ls'
            ),
        );
    }
}

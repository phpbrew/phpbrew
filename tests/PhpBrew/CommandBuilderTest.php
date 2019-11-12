<?php

namespace PhpBrew\Tests;

use PhpBrew\CommandBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class CommandBuilderTest extends TestCase
{
    public function test()
    {
        ob_start();
        $cmd = new CommandBuilder('ls');
        $this->assertEquals(0, $cmd->execute());
        ob_end_clean();
    }

    /**
     * @dataProvider provideTestGetCommandTestCases
     */
    public function testGetCommand($appendLog, $stdout, $logPath, $expected)
    {
        $cmd = new CommandBuilder('ls');
        $cmd->setAppendLog($appendLog);
        $cmd->setStdout($stdout);
        $cmd->setLogPath($logPath);
        $this->assertEquals($expected, $cmd->buildCommand());
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
                'expected'      => 'ls 2>&1'
            ),
            array(
                'appendLog' => false,
                'stdout'    => true,
                'logPath'   => '/tmp/build.log',
                'expected'      => 'ls 2>&1'
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

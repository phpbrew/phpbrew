<?php

use PhpBrew\Machine;

class MachineTest extends \PHPUnit_Framework_TestCase
{
    public function testDetectProcessorNumber()
    {
        $machine = new MachineForTest();
        if (!$machine->detectProcessorNumber()) {
            return $this->markTestSkipped('processor number detect failed.');
        }

        ok($machine->detectProcessorNumber() > 0);
        is(
            $machine->detectProcessorNumber(),
            $machine->detectProcessorNumberByNproc() || $machine->detectProcessorNumberByGrep()
        );
    }
}

class MachineForTest extends Machine
{
    public function __construct()
    {
    }

    public function detectProcessorNumberByNproc()
    {
        return parent::detectProcessorNumberByNproc();
    }

    public function detectProcessorNumberByGrep()
    {
        return parent::detectProcessorNumberByGrep();
    }
}

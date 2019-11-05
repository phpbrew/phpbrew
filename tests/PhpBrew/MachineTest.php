<?php

namespace PhpBrew\Tests;

use PhpBrew\Machine;
use PHPUnit\Framework\TestCase;

class MachineTest extends TestCase
{
    public function testDetectProcessorNumber()
    {
        $machine = new MachineForTest();
        if (!$machine->detectProcessorNumber()) {
            $this->markTestSkipped('processor number detect failed.');
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

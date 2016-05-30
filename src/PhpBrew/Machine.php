<?php

namespace PhpBrew;

/**
 * TODO
 * merge this class into PhpBrew\Platform\Hardware.
 */
class Machine
{
    private static $instance;
    private $processorNumber;

    public function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function detectProcessorNumber()
    {
        if ($this->processorNumber) {
            return $this->processorNumber;
        }

        if ($this->processorNumber = $this->detectProcessorNumberByNproc()) {
            return $this->processorNumber;
        }

        if ($this->processorNumber = $this->detectProcessorNumberByGrep()) {
            return $this->processorNumber;
        }

        return;
    }

    protected function detectProcessorNumberByNproc()
    {
        if (Utils::findBin('nproc')) {
            $process = new Process('nproc');
            $process->run();
            $this->processorNumber = intval($process->getOutput());

            return $this->processorNumber;
        }

        return;
    }

    protected function detectProcessorNumberByGrep()
    {
        if (Utils::findBin('grep') && file_exists('/proc/cpuinfo')) {
            $process = new Process('grep -c ^processor /proc/cpuinfo 2>/dev/null');
            $process->run();
            $this->processorNumber = intval($process->getOutput());

            return $this->processorNumber;
        }

        return;
    }
}

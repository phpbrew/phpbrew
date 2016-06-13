<?php

namespace PhpBrew\Exception;

use RuntimeException;
use PhpBrew\Build;
use PhpBrew\Buildable;

class SystemCommandException extends RuntimeException
{
    protected $logFile;

    protected $build;

    public function __construct($message, Buildable $build = null, $logFile = null)
    {
        parent::__construct($message);
        $this->build = $build;
        $this->logFile = $logFile;
    }

    public function getLogFile()
    {
        if ($this->logFile) {
            return $this->logFile;
        } else if ($this->build) {
            return $this->build->getBuildLogPath();
        }
    }

    public function getBuild()
    {
        return $this->build;
    }
}

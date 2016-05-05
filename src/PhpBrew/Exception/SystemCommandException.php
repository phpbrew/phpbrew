<?php
namespace PhpBrew\Exception;

use RuntimeException;
use PhpBrew\Build;

class SystemCommandException extends RuntimeException
{
    protected $logFile;

    protected $build;

    public function __construct($message, Build $build = null, $logFile = null)
    {
        parent::__construct($message);
        $this->build = $build;
        $this->logFile = $logFile;
    }

    public function getLogFile()
    {
        if ($this->logFile) {
            return $this->logFile;
        } elseif ($this->build) {
            return $this->build->getBuildLogPath();
        }
    }

    public function getBuild()
    {
        return $this->build;
    }
}

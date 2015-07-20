<?php
namespace PhpBrew\Exception;
use RuntimeException;

class SystemCommandException extends RuntimeException
{
    protected $logFile;

    public function __construct($message, $logFile = null)
    {
        parent::__construct($message);
        $this->logFile = $logFile;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }
}




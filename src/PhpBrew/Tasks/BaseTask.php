<?php
namespace PhpBrew\Tasks;
use CLIFramework\Logger;

class BaseTask
{
    public $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function info($msg)
    {
        if ($this->logger) {
            $this->logger->info($msg);
        }
    }

    public function debug($msg)
    {
        if ($this->logger) {
            $this->logger->debug($msg);
        }
    }

}

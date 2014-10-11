<?php
namespace PhpBrew\Tasks;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;

class BaseTask
{
    public $logger;

    public $options;

    public function __construct(Logger $logger, OptionResult $options = NULL)
    {
        $this->logger = $logger;
        if ($options) {
            $this->options = $options;
        }
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

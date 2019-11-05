<?php

namespace PhpBrew\Tasks;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;

abstract class BaseTask
{
    /**
     * @var Logger
     */
    public $logger;

    public $options;

    public $startedAt;

    public $finishedAt;

    public function __construct(Logger $logger, OptionResult $options = null)
    {
        $this->startedAt = microtime(true);
        $this->logger = $logger;
        if ($options) {
            $this->options = $options;
        } else {
            $this->options = new OptionResult();
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

    public function __destruct()
    {
        $this->finishedAt = microtime(true);
    }
}

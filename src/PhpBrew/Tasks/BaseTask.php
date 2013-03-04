<?php

namespace PhpBrew\Tasks;

class BaseTask
{
    public $logger;

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
        if($this->logger)
            $this->logger->info($msg);
    }

    public function debug($msg)
    { 
        if($this->logger)
            $this->logger->debug($msg);
    }

}




<?php
namespace PhpBrew\Exception;

use Exception;
use PhpBrew\Tasks\BaseTask;

class TaskFailureException extends Exception
{
    public $task;

    public function __construct(BaseTask $task, $message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getTask()
    {
        return $this->task;
    }
}

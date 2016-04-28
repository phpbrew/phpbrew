<?php
namespace PhpBrew;
use PhpBrew\Exception\SystemCommandException;

class CommandBuilder
{

    /* process nice value */
    public $nice;

    /* script */
    public $script;

    /* arguments */
    public $args = array();

    public $stdout;

    public $stderr;

    public $append = true;

    public $logPath;


    public function __construct($script)
    {
        $this->script = $script;
    }

    public function args($args)
    {
        $this->args = $args;
    }

    public function addArg($arg)
    {
        $this->args[] = $arg;
    }

    public function arg($arg)
    {
        $this->args[] = $arg;
    }

    public function nice($nice)
    {
        $this->nice = $nice;
    }


    public function execute(& $lastline = null)
    {
        $ret = null;
        $command = $this->getCommand();
        $lastline = system($command, $ret);
        if ($lastline === false) {
            return $ret;
        }
        if ($ret != 0) {
            // XXX: improve this later.
            echo substr($lastline,0, 78) . "\n";
        }
        return $ret;
    }

    public function __toString()
    {
        return $this->getCommand();
    }

    public function setStdout($stdout = true)
    {
        $this->stdout = $stdout;
    }

    public function setAppendLog($append = true)
    {
        $this->append = $append;
    }

    public function setLogPath($logPath)
    {
        $this->logPath = $logPath;
    }

    public function getCommand()
    {
        $cmd = array();

        if ($this->nice) {
            $cmd[] = 'nice';
            $cmd[] = '-n';
            $cmd[] = $this->nice;
        }
        $cmd[] = $this->script;

        if ($this->args) {
            foreach ($this->args as $arg) {
                $cmd[] = escapeshellarg($arg);
            }
        }

        // redirect stderr to stdout and pipe to the file.
        if ($this->stdout && $this->logPath) {
            $cmd[] = '| tee';
            if ($this->append) {
                $cmd[] = '-a';
            }
            $cmd[] = $this->logPath;
            $cmd[] = '2>&1';
        } elseif ($this->logPath) {
            $cmd[] = $this->append ? '>>' : '>';
            $cmd[] = $this->logPath;
            $cmd[] = '2>&1';
        }
        return join(' ', $cmd);
    }
}

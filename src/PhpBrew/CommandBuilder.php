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

    public function passthru(& $lastline = null)
    {
        $ret = null;
        $command = $this->buildCommand(false);
        $lastline = passthru($command, $ret);
        if ($lastline === false) {
            return $ret;
        }
        return $ret;
    }

    public function execute(& $lastline = null)
    {
        $ret = null;
        $command = $this->buildCommand();
        $lastline = system($command, $ret);
        if ($lastline === false) {
            return $ret;
        }
        return $ret;
    }

    public function __toString()
    {
        return $this->buildCommand();
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

    public function buildCommand($handleRedirect = true)
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
        if ($handleRedirect) {
            if ($this->stdout) {
                // XXX: tee is disabled here because the exit status won't be
                // correct when using pipe.
                /*
                $cmd[] = '| tee';
                if ($this->append) {
                    $cmd[] = '-a';
                }
                $cmd[] = $this->logPath;
                 */
                $cmd[] = '2>&1';
            } elseif ($this->logPath) {
                $cmd[] = $this->append ? '>>' : '>';
                $cmd[] = $this->logPath;
                $cmd[] = '2>&1';
            }
        }
        return join(' ', $cmd);
    }
}

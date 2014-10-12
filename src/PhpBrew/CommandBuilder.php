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

    public function execute()
    {
        $ret = null;
        $command = $this->getCommand();
        $line = system($command, $ret);
        if ($ret != 0) {
            // XXX: improve this later.
            echo substr($line,0, 78) . "\n";
        }
        return $ret;
    }

    public function __toString()
    {
        return $this->getCommand();
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

        /* can redirect stderr to stdout */
        if ($this->stdout) {
            // redirect stderr to stdout
            $cmd[] = '2>';
            $cmd[] = $this->stdout;
            $cmd[] = '1>';
            $cmd[] = $this->stdout;
        }
        if ($this->stderr) {
            $cmd[] = '2>';
            $cmd[] = $this->stderr;
        }
        return join(' ', $cmd);
    }
}

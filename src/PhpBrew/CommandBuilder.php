<?php
namespace PhpBrew;


class CommandBuilder
{

    /* process nice value */
    public $nice;


    /* script */
    public $script;

    /* arguments */
    public $args = array();


    public $redirectStderrToStdout;

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

    public function nice($nice)
    {
        $this->nice = $nice;
    }

    public function execute() {
        $command = $this->getCommand();
        return system( $command );
    }

    public function getCommand()
    {
        $cmd = array();

        if( $this->nice ) {
            $cmd[] = 'nice';
            $cmd[] = '-n';
            $cmd[] = $this->nice;
        }

        $cmd[] = $this->script;

        if( $this->args ) {
            foreach( $this->args as $arg ) {
                $cmd[] = escapeshellarg($arg);
            }
        }

        /* can redirect stderr to stdout */
        if( $this->redirectStderrToStdout ) {
            $cmd[] = '2>&1';
        }

        if( $this->stdout ) {
            $cmd[] = '>';
            $cmd[] = $this->stdout;
        }

        if( $this->stderr ) {
            $cmd[] = '2>&';
            $cmd[] = $this->stderr;
        }
        return join(' ',$cmd);
    }

}


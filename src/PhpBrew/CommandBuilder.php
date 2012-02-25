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
        $cmd = array();

        if( $this->nice ) {
            $cmd[] = 'nice';
            $cmd[] = '-n';
            $cmd[] = $this->nice;
        }

        $cmd[] = $this->script;

        foreach( $this->args as $arg ) {
            $cmd[] = $arg;
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

        return system(join(' ',$cmd));
        // system( $command . ' > /dev/null' ) !== false or die('Test failed.');
    }

}


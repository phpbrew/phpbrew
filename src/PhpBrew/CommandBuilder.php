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

    public $stdout;

    public $stderr;

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

        return system(join(' ',$cmd));
        // system( $command . ' > /dev/null' ) !== false or die('Test failed.');
    }


}





<?php
namespace PhpBrew;
use CLIFramework\Application;

class Console extends Application 
{

    public function init()
    {
        parent::init();
        $this->registerCommand('install');
        // $this->registerCommand('known');
    }

    public function brief()
    {
        return 'brew your latest php!';
    }

}

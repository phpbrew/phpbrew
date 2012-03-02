<?php
namespace PhpBrew;
use CLIFramework\Application;

class Console extends Application 
{

    public function init()
    {
        parent::init();
        $this->registerCommand('init');
        $this->registerCommand('install');
        $this->registerCommand('known');
        $this->registerCommand('list');
        $this->registerCommand('env');
        $this->registerCommand('use');
        $this->registerCommand('switch');
        $this->registerCommand('variants');
        $this->registerCommand('config');
    }

    public function brief()
    {
        return 'brew your latest php!';
    }


}

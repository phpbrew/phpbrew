<?php
namespace PhpBrew;
use CLIFramework\Application;

class Console extends Application 
{
    const app_name = 'phpbrew';
    const app_version = '1.2.1';

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
        $this->registerCommand('self-update', 'PhpBrew\Command\SelfUpdateCommand');
    }

    public function brief()
    {
        return 'brew your latest php!';
    }


}

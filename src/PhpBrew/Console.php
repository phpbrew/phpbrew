<?php
namespace PhpBrew;
use CLIFramework\Application;

class Console extends Application 
{
    const name = 'phpbrew';
<<<<<<< HEAD
    const version = '1.5.0';
=======
    const version = '1.4.1';
>>>>>>> d1270e9918ea55898b8265011559b2e9b6d82d8b

    public function init()
    {
        parent::init();
        $this->registerCommand('init');
        $this->registerCommand('install');
        $this->registerCommand('info');
        $this->registerCommand('known');
        $this->registerCommand('list');
        $this->registerCommand('env');
        $this->registerCommand('use');
        $this->registerCommand('switch');
        $this->registerCommand('variants');
        $this->registerCommand('config');
        $this->registerCommand('install-ext');

        $this->registerCommand('enable', 'PhpBrew\Command\EnableExtensionCommand');
        $this->registerCommand('self-update', 'PhpBrew\Command\SelfUpdateCommand');
    }

    public function brief()
    {
        return 'brew your latest php!';
    }


}

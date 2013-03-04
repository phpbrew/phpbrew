<?php
namespace PhpBrew;
use CLIFramework\Application;

class Console extends Application
{
    const name = 'phpbrew';
    const version = '1.6.5';

    public function init()
    {
        parent::init();
        $this->registerCommand('init');
        $this->registerCommand('known');
        $this->registerCommand('install');
        $this->registerCommand('list');
        $this->registerCommand('use');
        $this->registerCommand('switch');

        $this->registerCommand('info');
        $this->registerCommand('env');
        $this->registerCommand('variants');
        $this->registerCommand('config');
        $this->registerCommand('download');
        $this->registerCommand('clean');

        $this->registerCommand('enable', 'PhpBrew\Command\EnableExtensionCommand');
        $this->registerCommand('install-ext');

        $this->registerCommand('self-update', 'PhpBrew\Command\SelfUpdateCommand');

        $this->registerCommand('remove');
        $this->registerCommand('purge');
    }

    public function brief()
    {
        return 'brew your latest php!';
    }
}

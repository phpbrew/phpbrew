<?php
namespace PhpBrew;

use CLIFramework\Application;

class Console extends Application
{
    const NAME = 'phpbrew';
    const VERSION = "1.14.3";

    public function init()
    {
        parent::init();
        $this->registerCommand('init');
        $this->registerCommand('known');
        $this->registerCommand('install');
        $this->registerCommand('list');
        $this->registerCommand('use');
        $this->registerCommand('switch');
        $this->registerCommand('each');

        $this->registerCommand('config');
        $this->registerCommand('info');
        $this->registerCommand('env');
        $this->registerCommand('ext');
        $this->registerCommand('variants');
        $this->registerCommand('path');
        $this->registerCommand('cd');
        $this->registerCommand('download');
        $this->registerCommand('clean');

        $this->registerCommand('list-ini', 'PhpBrew\Command\ListIniCommand');

        $this->registerCommand('ctags', 'PhpBrew\Command\CtagsCommand');

        $this->registerCommand('enable', 'PhpBrew\Command\MigratedCommand');
        $this->registerCommand('install-ext', 'PhpBrew\Command\MigratedCommand');

        $this->registerCommand('self-update', 'PhpBrew\Command\SelfUpdateCommand');

        $this->registerCommand('remove');
        $this->registerCommand('purge');

        $this->registerCommand('off');
        $this->registerCommand('switch-off', 'PhpBrew\Command\SwitchOffCommand');

        $this->configure();
    }

    public function configure()
    {
        // avoid warnings when web scraping malformed HTML
        libxml_use_internal_errors(true);
        // prevent execution time limit fatal error
        set_time_limit(0);
        // prevent warnings when timezone is not set
        date_default_timezone_set(
            is_readable($tz = '/etc/timezone') ? trim(file_get_contents($tz)) : 'America/Los_Angeles'
        );
    }

    public function brief()
    {
        return 'brew your latest php!';
    }
}

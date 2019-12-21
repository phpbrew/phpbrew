<?php

namespace PHPBrew\Command;

use CLIFramework\Command;

class AppCommand extends Command
{
    public $apps = array(
        'composer' => array('url' => 'https://getcomposer.org/composer.phar', 'as' => 'composer'),
        'phpunit' => array('url' => 'https://phar.phpunit.de/phpunit.phar', 'as' => 'phpunit'),
        'phpmd' => array('url' => 'http://static.phpmd.org/php/latest/phpmd.phar', 'as' => 'phpmd'),
    );


    public function brief()
    {
        return '[deprecated] php app store';
    }

    public function init()
    {
        parent::init();
        $this->command('get');
        $this->command('list');
    }

    public function execute()
    {
        $listCommand = $this->getCommand('list');
        $listCommand->execute();
    }
}

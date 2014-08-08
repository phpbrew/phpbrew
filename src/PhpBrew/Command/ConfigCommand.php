<?php
namespace PhpBrew\Command;

use PhpBrew\Config;

class ConfigCommand extends AbstractConfigCommand
{
    public function brief()
    {
        return 'edit your current php.ini in your favorite $EDITOR';
    }

    public function execute()
    {
        $root = Config::getPhpbrewRoot();
        $php  = Config::getCurrentPhpName();
        $file = "{$root}/php/{$php}/etc/php.ini";
        $this->editor($file);
    }
}

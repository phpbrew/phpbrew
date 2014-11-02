<?php
namespace PhpBrew\Command;
use CLIFramework\Command;
use PhpBrew\Utils;
use PhpBrew\Config;

class ConfigCommand extends Command
{
    public function brief()
    {
        return 'Edit your current php.ini in your favorite $EDITOR';
    }

    public function execute()
    {
        $root = Config::getPhpbrewRoot();
        $php  = Config::getCurrentPhpName();
        $file = "{$root}/php/{$php}/etc/php.ini";
        Utils::editor($file);
    }
}

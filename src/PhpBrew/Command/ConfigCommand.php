<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\Config;
use PhpBrew\Utils;

class ConfigCommand extends Command
{
    public function usage()
    {
        return 'phpbrew config [--sapi]';
    }

    public function brief()
    {
        return 'Edit your current php.ini in your favorite $EDITOR';
    }

    public function options($opts)
    {
        $opts->add('s|sapi:=string', 'Edit php.ini for SAPI name.');
    }

    public function execute()
    {
        $sapi = 'cli';
        if ($this->options->sapi) {
            $sapi = $this->options->sapi;
        }

        $file = Config::getVersionEtcPath(Config::getCurrentPhpName()) . '/' . $sapi . '/php.ini';

        return Utils::editor($file) === 0;
    }
}

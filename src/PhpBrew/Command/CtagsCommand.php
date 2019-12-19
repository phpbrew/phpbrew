<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\BuildFinder;
use PhpBrew\CommandBuilder;
use PhpBrew\Config;

class CtagsCommand extends Command
{
    public function brief()
    {
        return 'Run ctags at current php source dir for extension development.';
    }

    public function arguments($args)
    {
        $args->add('PHP build')
            ->validValues(function () {
                return BuildFinder::findInstalledBuilds();
            })
            ;
    }

    public function execute($versionName = null)
    {
        $args = func_get_args();
        array_shift($args);

        if ($versionName) {
            $sourceDir = Config::getBuildDir() . DIRECTORY_SEPARATOR . $versionName;
        } else {
            if (!getenv('PHPBREW_PHP')) {
                $this->logger->error(<<<EOF
Error: PHPBREW_PHP environment variable is not defined.
  This command requires you specify a PHP version from your build list.
  And it looks like you haven't switched to a version from the builds that were built with PHPBrew.
Suggestion: Please install at least one PHP with your preferred version and switch to it.
EOF
                );

                return;
            }
            $sourceDir = Config::getCurrentBuildDir();
        }
        if (!file_exists($sourceDir)) {
            $this->logger->error("$sourceDir does not exist.");

            return;
        }
        $this->logger->info('Scanning ' . $sourceDir);

        $cmd = new CommandBuilder('ctags');
        $cmd->arg('-R');
        $cmd->arg('-a');
        $cmd->arg('-h');
        $cmd->arg('.c.h.cpp');

        $cmd->arg($sourceDir . DIRECTORY_SEPARATOR . 'main');
        $cmd->arg($sourceDir . DIRECTORY_SEPARATOR . 'ext');
        $cmd->arg($sourceDir . DIRECTORY_SEPARATOR . 'Zend');

        foreach ($args as $a) {
            $cmd->arg($a);
        }

        $this->logger->debug($cmd->__toString());
        $cmd->execute();

        $this->logger->info('Done');
    }
}

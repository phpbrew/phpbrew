<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\Build;
use PhpBrew\CommandBuilder;

class CtagsCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Run ctags at current php source dir for extension development.';
    }

    public function execute()
    {
        $args = func_get_args();

        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();
        $buildDir = Config::getBuildDir();
        $version = getenv('PHPBREW_PHP');

        // XXX: get source dir from current build information
        $sourceDir = $buildDir . DIRECTORY_SEPARATOR . $version;
        $this->logger->info($sourceDir);

        $cmd = new CommandBuilder('ctags');
        $cmd->arg('--recurse');
        $cmd->arg('-a');
        $cmd->arg('-h');
        $cmd->arg('.c.h.cpp');

        $cmd->arg($sourceDir . DIRECTORY_SEPARATOR . 'main');
        $cmd->arg($sourceDir . DIRECTORY_SEPARATOR . 'ext');
        $cmd->arg($sourceDir . DIRECTORY_SEPARATOR . 'Zend');

        foreach ($args as $a) {
            $cmd->arg($a);
        }

        $this->logger->info($cmd->__toString());
        $cmd->execute();

        $this->logger->info("Done");
    }
}

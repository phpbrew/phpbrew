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


    public function execute($versionName = NULL)
    {
        $args = func_get_args();
        array_shift($args);


        // $currentVersion;
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();

        // XXX: get source dir from current build information
        $sourceDir = Config::getCurrentBuildDir();
        if (!$versionName) {
            $sourceDir = Config::getBuildDir() . DIRECTORY_SEPARATOR . $versionName;
        }
        if (!file_exists($sourceDir)) {
            return $this->logger->error("$sourceDir does not exist.");
        }
        $this->logger->info("Scanning " . $sourceDir);

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

        $this->logger->debug($cmd->__toString());
        $cmd->execute();

        $this->logger->info("Done");
    }
}

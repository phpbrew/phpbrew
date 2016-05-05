<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\CommandBuilder;

class CtagsCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Run ctags at current php source dir for extension development.';
    }

    public function arguments($args)
    {
        $args->add('installed versions')
            ->validValues(function () { return \PhpBrew\Config::getInstalledPhpVersions(); })
            ;
    }

    public function execute($versionName = null)
    {
        $args = func_get_args();
        array_shift($args);


        // $currentVersion;
        $root = Config::getRoot();
        $home = Config::getHome();

        if ($versionName) {
            $sourceDir = Config::getBuildDir() . DIRECTORY_SEPARATOR . $versionName;
        } else {
            if (!getenv('PHPBREW_PHP')) {
                $this->logger->error("Error: PHPBREW_PHP environment variable is not defined.");
                $this->logger->error("  This command requires you specify a PHP version from your build list.");
                $this->logger->error("  And it looks like you haven't switched to a version from the builds that were built with PHPBrew.");
                $this->logger->error("Suggestion: Please install at least one PHP with your prefered version and switch to it.");
                return false;
            }
            $sourceDir = Config::getCurrentBuildDir();
        }
        if (!file_exists($sourceDir)) {
            return $this->logger->error("$sourceDir does not exist.");
        }
        $this->logger->info("Scanning " . $sourceDir);

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

        $this->logger->info("Done");
    }
}

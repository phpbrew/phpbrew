<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Utils;
use PhpBrew\Config;
use Exception;

class EnableCommand extends \CLIFramework\Command
{
    public function usage() { return 'phpbrew ext enable [extension name]'; }

    public function brief() { return 'Enable PHP extension'; }

    public function execute($extensionName)
    {
        if( ! extension_loaded($extensionName) ) {
            $path = Utils::create_extension_config($extensionName);
            $this->logger->debug("Writing extension config file: $path.");
            $this->logger->info("$extensionName is enabled now.");
        } else {
            $this->logger->info("$extensionName is already enabled.");
        }
    }
}

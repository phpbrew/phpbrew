<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use PhpBrew\Utils;
use Exception;

class EnableExtensionCommand extends \CLIFramework\Command
{

    public function usage() { return 'phpbrew enable [extension name]'; }

    public function brief() { return 'enable extension'; }

    public function execute($extensionName)
    {
        if( ! extension_loaded($extensionName) ) {
            Utils::create_extension_config($extensionName);
            $this->logger->info("$extensionName is enabled now.");
        } else {
            $this->logger->info("$extensionName is already enabled.");
        }
    }
}

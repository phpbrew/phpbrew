<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Utils;
use PhpBrew\Config;
use CLIFramework\Command;
use Exception;

class DisableCommand extends Command
{
    public function usage() { return 'phpbrew ext disable [extension name]'; }

    public function brief() { return 'Disable PHP extension'; }

    public function execute($name)
    {
        if( extension_loaded($name) ) {
            $path = Utils::get_extension_config_path( $name );
            if ( file_exists($path) ) {
                $this->logger->debug("Found extension config file: $path");
                $lines = file($path);
                foreach( $lines as &$line ) {
                    if ( preg_match('#^(?:zend_)?extension\s*=#', $line ) ) {
                        $line = '; ' . $line;
                    }
                }
                // write back
                file_put_contents($path, join("\n",$lines));
                $this->logger->info("Extension $name is now disabled.");
            } else {
                $this->logger->info("Extension $name can not be disabled.");
            }
        } else {
            $this->logger->info("Extension $name is already disabled.");
        }
    }
}

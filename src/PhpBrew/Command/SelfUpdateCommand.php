<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use CLIFramework\Command;

class SelfUpdateCommand extends Command
{

    public function brief() { return 'self-update'; }

    public function execute()
    {
        global $argv;
        $script = realpath( $argv[0] );
        if( ! is_writable($script) ) {
            throw new Exception("$script is not writable.");
        }

        // fetch new version phpbrew
        $this->getLogger()->info("Fetching phpbrew to $script...");
        $phar = file_get_contents('https://raw.github.com/c9s/phpbrew/master/phpbrew');
        if( false === file_put_contents( $script, $phar ) ) {
            throw new Exception("Can't not write content. $script");
        }

        $this->getLogger()->info("Version updated.");
    }
}





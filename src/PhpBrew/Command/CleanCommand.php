<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\Variants;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\DirectorySwitch;

use CLIFramework\Command;

class CleanCommand extends Command
{
    public function brief() { return 'clean up php distribution'; }

    public function usage() 
    {
        return 'phpbrew clean [php-version]';
    }

    public function options($opts)
    {
    }

    public function execute($version)
    {
        $info = PhpSource::getVersionInfo( $version, $this->options->old );
        if( ! $info)
            throw new Exception("Version $version not found.");

        $clean = new CleanTask($this->logger);
        if( $clean->cleanByVersion($version) ) {
            $this->logger->info("Distribution is cleaned up. Woof! ");
        }
    }
}


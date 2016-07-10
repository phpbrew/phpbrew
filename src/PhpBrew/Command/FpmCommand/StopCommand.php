<?php

namespace PhpBrew\Command\FpmCommand;

use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Build;
use CLIFramework\Command;
use PhpBrew\Exception\SystemCommandException;
use Exception;

class StopCommand extends Command
{
    public function brief()
    {
        return 'Stop fpm';
    }
    
    public function execute()
    {
    }
}

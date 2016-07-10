<?php

namespace PhpBrew\Command\FpmCommand;

use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Build;
use CLIFramework\Command;
use PhpBrew\Exception\SystemCommandException;
use Exception;

class StartCommand extends Command
{
    public function brief()
    {
        return 'Start fpm';
    }
    
    public function execute()
    {
    }
}

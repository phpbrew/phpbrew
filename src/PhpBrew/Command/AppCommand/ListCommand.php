<?php
namespace PhpBrew\Command\AppCommand;
use PhpBrew\Downloader\UrlDownloader;
use PhpBrew\Config;
use PhpBrew\AppStore;
use CLIFramework\Command;
use Exception;

class ListCommand extends Command
{
    public function brief()
    {
        return 'list php apps';
    }

    public function execute() 
    {
        $apps = AppStore::all();
        foreach ($apps as $name => $opt) {
            $this->logger->writeln(sprintf('% -8s - %s', $name , $opt['url']));
        }
    }
}



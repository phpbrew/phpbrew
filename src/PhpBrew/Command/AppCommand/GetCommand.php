<?php

namespace PhpBrew\Command\AppCommand;

use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Config;
use PhpBrew\AppStore;
use CLIFramework\Command;
use Exception;

class GetCommand extends Command
{
    public function brief()
    {
        return 'get php app';
    }

    public function options($opts)
    {
        $opts->add('chmod:');
        DownloadFactory::addOptionsForCommand($opts);
    }

    public function arguments($args)
    {
        $apps = AppStore::all();
        $args->add('app-name')
            ->validValues(array_keys($apps))
            ;
    }

    public function execute($appName)
    {
        $apps = AppStore::all();

        if (!isset($apps[$appName])) {
            throw new Exception("App $appName not found.");
        }
        $app = $apps[$appName];
        $targetDir = Config::getRoot().DIRECTORY_SEPARATOR.'bin';
        $target = $targetDir.DIRECTORY_SEPARATOR.$app['as'];

        DownloadFactory::getInstance($this->logger, $this->options)->download($app['url'], $target);

        $this->logger->info('Changing permissions to 0755');

        if ($mod = $this->options->chmod) {
            chmod($target, octdec($mod));
        } else {
            chmod($target, 0755);
        }

        $this->logger->info("Downloaded at $target");
    }
}

<?php

namespace PHPBrew\Command\AppCommand;

use CLIFramework\Command;
use Exception;
use PHPBrew\AppStore;
use PHPBrew\Config;
use PHPBrew\Downloader\DownloadFactory;

class GetCommand extends Command
{
    public function brief()
    {
        return '[deprecated] Get PHP application';
    }

    public function options($opts)
    {
        $opts->add('chmod:', 'Set downloaded file mode');
        DownloadFactory::addOptionsForCommand($opts);
    }

    public function arguments($args)
    {
        $apps = AppStore::all();
        $args->add('app-name')
            ->desc('Application name')
            ->validValues(array_keys($apps))
            ;
    }

    public function execute($appName)
    {
        $this->logger->warn(
            'The app command and its subcommands are deprecated and will be removed in the future.' . PHP_EOL
            . 'Please consider switching to PHIVE (https://phar.io/).'
        );

        $apps = AppStore::all();

        if (!isset($apps[$appName])) {
            throw new Exception("App $appName not found.");
        }

        $app = $apps[$appName];
        $targetDir = Config::getRoot() . DIRECTORY_SEPARATOR . 'bin';
        $target = $targetDir . DIRECTORY_SEPARATOR . $app['as'];

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

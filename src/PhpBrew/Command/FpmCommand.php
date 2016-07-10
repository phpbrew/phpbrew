<?php

namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\VariantParser;
use PhpBrew\VariantBuilder;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Build;
use PhpBrew\ReleaseList;
use PhpBrew\VersionDslParser;
use PhpBrew\BuildSettings\DefaultBuildSettings;
use PhpBrew\Distribution\DistributionUrlPolicy;
use CLIFramework\ValueCollection;
use CLIFramework\Command;
use PhpBrew\Exception\SystemCommandException;
use Exception;


class FpmCommand extends Command
{
    public function brief()
    {
        return 'fpm commands';
    }

    public function init()
    {
        parent::init();
        $this->command('setup');
        $this->command('start');
        $this->command('stop');
    }

    public function execute()
    {
    }
}


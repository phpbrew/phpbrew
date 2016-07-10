<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 4/29/2016
 * Time: 5:23 PM
 */

namespace PhpBrew\Command;

use PhpBrew\Command\ApacheCommand\BaseCommand;
use PhpBrew\Utils\ApacheUtils;

class ApacheCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew apache [switch]';
    }

    public function brief()
    {
        return 'Show basic info about apache and execute apache subcommands';
    }

    public function init()
    {
        parent::init();
        $this->command('switch');
    }

    /**
     * @param GetOptionKit\OptionCollection $opts
     */
    public function options($opts)
    {
    }

    public function execute()
    {
        $this->logger->info("Apache config file path: " . ApacheUtils::getApacheConfigPath());
    }
}
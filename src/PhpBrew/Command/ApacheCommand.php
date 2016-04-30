<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 4/29/2016
 * Time: 5:23 PM
 */

namespace PhpBrew\Command;


use PhpBrew\Command\ApacheCommand\BaseCommand;

class ApacheCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew apache [switch]';
    }

    public function brief()
    {
        return 'Execute apache subcommands';
    }

    public function init()
    {
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

    }
}
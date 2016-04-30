<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 4/29/2016
 * Time: 5:26 PM
 */

namespace PhpBrew\Command\ApacheCommand;


use CLIFramework\Command;
use PhpBrew\Config;
use PhpBrew\Tasks\Apxs2CheckTask;

class BaseCommand extends Command
{
    public function prepare()
    {
        parent::prepare();

        $configPath = Config::getPhpbrewConfigDir() . DIRECTORY_SEPARATOR . 'apache.conf';

        //config not exists. maybe there's no build with +apxs2 or just upgrade from an old phpbrew
        if (!file_exists($configPath)) {
            $check = new Apxs2CheckTask($this->getLogger());
            $check->check();
        }

        return true;
    }
}
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
        return true;
    }
}
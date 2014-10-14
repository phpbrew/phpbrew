<?php
namespace PhpBrew\Tasks;
use PhpBrew\CommandBuilder;

/**
 * Task to run `make test`
 */
class TestTask extends BaseTask
{
    public function run(Build $build, $nice = null)
    {
        $this->info("Testing...");
        $cmd = new CommandBuilder('make test');

        if ($nice) {
            $cmd->nice($nice);
        }

        $cmd->setAppendLog(true);
        $cmd->setLogPath($build->getBuildLogPath());

        $this->debug('' .  $cmd);
        $code = $cmd->execute();
        if ($code != 0)
            die('Test failed.');
    }
}

<?php
namespace PhpBrew\Tasks;

use PhpBrew\CommandBuilder;

/**
 * Task to run `make test`
 */
class TestTask extends BaseTask
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function test($nice = null)
    {
        $this->info("Testing...");
        $cmd = new CommandBuilder('make test');

        if ($nice !== null) {
            $cmd->nice($nice);
        }

        $cmd->append = true;

        if ($this->logPath !== null) {
            $cmd->stdout = $this->logPath;
        }

        $this->debug('' .  $cmd);
        $cmd->execute() !== false or die('Test failed.');
    }
}

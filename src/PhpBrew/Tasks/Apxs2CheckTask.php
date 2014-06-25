<?php
namespace PhpBrew\Tasks;

use Exception;
use PhpBrew\Utils;

class Apxs2CheckTask extends BaseTask
{
    /**
     * @param \PhpBrew\Build $build
     * @throws \Exception
     */
    public function check($build)
    {
        $apxs = $build->getVariant('apxs2');

        if ($apxs === null) {
            $apxs = Utils::findBin('apxs');
        }

        if ($apxs !== null) {
            $this->logger->debug("Found apxs2 sbin: $apxs");

            $libDir = Utils::pipeExecute("$apxs -q LIBEXECDIR");

            // use apxs to check module dir permission
            if (false !== $libDir && false === is_writable($libDir)) {
                throw new Exception("Apache module dir $libDir is not writable.\nPlease consider using chmod or sudo.");
            }

            $confDir = Utils::pipeExecute("$apxs -q SYSCONFDIR");

            if (false !== $confDir && false === is_writable($confDir)) {
                $msg = array();
                $msg[] = "Apache conf dir $confDir is not writable for phpbrew.";
                $msg[] = "Please consider using chmod or sudo: ";
                $msg[] = "    \$ sudo chmod -R og+rw $confDir";
                throw new Exception(join("\n", $msg));
            }
        }
    }
}

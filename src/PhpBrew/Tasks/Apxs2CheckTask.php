<?php

namespace PhpBrew\Tasks;

use Exception;
use PhpBrew\Utils;
use PhpBrew\Build;

class Apxs2CheckTask extends BaseTask
{
    protected $apxs;

    public function check($build = '')
    {
        if ($build instanceof Build) {
            $apxs = $build->getVariant('apxs2');

            $this->apxs = $this->getExecutableApxs($apxs);
            $this->checkModuleDir();
            $this->checkConfigDir();
        } else {
            $this->apxs = $this->getExecutableApxs();
            $this->checkConfigDir();
        }
    }

    protected function getExecutableApxs($apxs = true)
    {
        // trying to find apxs binary in case it wasn't explicitly specified (+apxs variant without path)
        if ($apxs === true) {
            $apxs = Utils::findbin('apxs');
            $this->logger->debug("Found apxs2 binary: $apxs");
        }

        if (!is_executable($apxs)) {
            throw new Exception("apxs binary is not executable: $apxs");
        }
    }

    protected function checkModuleDir()
    {
        $apxs = $this->apxs;
        // use apxs to check module dir permission
        if ($apxs && $libdir = trim(Utils::pipeExecute("$apxs -q LIBEXECDIR"))) {
            if (false === is_writable($libdir)) {
                $this->logger->error("Apache module dir $libdir is not writable.\nPlease consider using chmod to change the folder permission:");
                $this->logger->error("    \$ sudo chmod -R oga+rw $libdir");
                $this->logger->error('Warnings: the command above is not safe for public systems. please use with discretion.');
                throw new Exception();
            }
        }
    }

    protected function checkConfigDir()
    {
        $apxs = $this->apxs;
        if ($apxs && $confdir = trim(Utils::pipeExecute("$apxs -q SYSCONFDIR"))) {
            if (false === is_writable($confdir)) {
                $this->logger->error("Apache conf dir $confdir is not writable for phpbrew.");
                $this->logger->error('Please consider using chmod to change the folder permission: ');
                $this->logger->error("    \$ sudo chmod -R oga+rw $confdir");
                $this->logger->error('Warnings: the command above is not safe for public systems. please use with discretion.');
                throw new Exception();
            }
        }
    }
}

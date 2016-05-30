<?php

namespace PhpBrew\Tasks;

use PhpBrew\Exception\SystemCommandException;
use PhpBrew\Build;
use PhpBrew\Patches\Apache2ModuleNamePatch;

/**
 * Task run before 'configure'.
 */
class BeforeConfigureTask extends BaseTask
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function run(Build $build)
    {
        if (!file_exists($build->getSourceDirectory().DIRECTORY_SEPARATOR.'configure')) {
            $this->debug("configure file not found, running './buildconf --force'...");
            $lastline = system('./buildconf --force', $status);
            if ($status !== 0) {
                throw new SystemCommandException("buildconf error: $lastline", $build);
            }
        }

        foreach ((array) $this->options->patch as $patchPath) {
            // copy patch file to here
            $this->info("===> Applying patch file from $patchPath ...");

            // Search for strip parameter
            for ($i = 0; $i <= 16; ++$i) {
                ob_start();
                system("patch -p$i --dry-run < $patchPath", $return);
                ob_end_clean();

                if ($return === 0) {
                    system("patch -p$i < $patchPath");
                    break;
                }
            }
        }

        // let's apply patch for libphp{php version}.so (apxs)
        if ($build->isEnabledVariant('apxs2')) {
            $apxs2Checker = new \PhpBrew\Tasks\Apxs2CheckTask($this->logger);
            $apxs2Checker->check($build, $this->options);
        }

        if (!$this->options->{'no-patch'}) {
            $this->logger->info('===> Checking patches...');
            $patches = array();
            $patches[] = new Apache2ModuleNamePatch();
            foreach ($patches as $patch) {
                $this->logger->info('Checking patch for '.$patch->desc());
                if ($patch->match($build, $this->logger)) {
                    $patched = $patch->apply($build, $this->logger);
                    $this->logger->info("$patched changes patched.");
                }
            }
        }
    }
}

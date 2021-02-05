<?php

namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\ConfigureParameters;
use PhpBrew\Patches\Apache2ModuleNamePatch;
use PhpBrew\Patches\FreeTypePatch;
use PhpBrew\Patches\ReadlinePatch;
use PhpBrew\PatchKit\Patch;

/**
 * Task run before 'configure'.
 */
class BeforeConfigureTask extends BaseTask
{
    public function run(Build $build, ConfigureParameters $parameters)
    {
        if (!file_exists($build->getSourceDirectory() . DIRECTORY_SEPARATOR . 'configure')) {
            $this->debug("configure file not found, running './buildconf --force'...");

            $buildConf = new BuildConfTask($this->logger);
            $buildConf->run($build);
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
            $apxs2Checker = new Apxs2CheckTask($this->logger);
            $apxs2Checker->check($parameters);
        }

        if (!$this->options->{'no-patch'}) {
            $this->logger->info('===> Checking patches...');

            $freeTypePatch = new FreeTypePatch();
            $readlinePatch = new ReadlinePatch();
            $needBuildConf = false;

            /** @var Patch[] $patches */
            $patches = array(
                new Apache2ModuleNamePatch($build->getVersion()),
                $freeTypePatch,
                $readlinePatch
            );

            foreach ($patches as $patch) {
                $this->logger->info('Checking patch for ' . $patch->desc());
                if ($patch->match($build, $this->logger)) {
                    $patched = $patch->apply($build, $this->logger);
                    $this->logger->info("$patched changes patched.");

                    if ($patch === $freeTypePatch || $patch === $readlinePatch) {
                        $needBuildConf = $patched;
                    }
                }
            }

            if ($needBuildConf) {
                $this->logger->info('Need to run buildconf');

                $buildConf = new BuildConfTask($this->logger);
                $buildConf->run($build);
            }
        }
    }
}

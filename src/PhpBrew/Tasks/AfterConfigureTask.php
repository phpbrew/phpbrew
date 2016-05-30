<?php

namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\Patches\IntlWith64bitPatch;
use PhpBrew\Patches\OpenSSLDSOPatch;
use PhpBrew\Patches\PHP53Patch;

/**
 * Task run before 'configure'.
 */
class AfterConfigureTask extends BaseTask
{
    public function setLogPath($path)
    {
        $this->logPath = $path;
    }

    public function run(Build $build)
    {
        if (!$this->options->{'no-patch'}) {
            $this->logger->info('===> Checking patches...');
            $patches = array();
            $patches[] = new PHP53Patch();
            $patches[] = new IntlWith64bitPatch();
            $patches[] = new OpenSSLDSOPatch();
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

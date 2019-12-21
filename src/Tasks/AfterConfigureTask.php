<?php

namespace PHPBrew\Tasks;

use PHPBrew\Build;
use PHPBrew\Patches\IntlWith64bitPatch;
use PHPBrew\Patches\OpenSSLDSOPatch;
use PHPBrew\Patches\PHP53Patch;
use PHPBrew\Patches\PHP56WithOpenSSL11Patch;

/**
 * Task run before 'configure'.
 */
class AfterConfigureTask extends BaseTask
{
    public function run(Build $build)
    {
        if (!$this->options->{'no-patch'}) {
            $this->logger->info('===> Checking patches...');
            $patches = array();
            $patches[] = new PHP53Patch();
            $patches[] = new IntlWith64bitPatch();
            $patches[] = new OpenSSLDSOPatch();
            $patches[] = new PHP56WithOpenSSL11Patch();
            foreach ($patches as $patch) {
                $this->logger->info('Checking patch for ' . $patch->desc());
                if ($patch->match($build, $this->logger)) {
                    $patched = $patch->apply($build, $this->logger);
                    $this->logger->info("$patched changes patched.");
                }
            }
        }
    }
}

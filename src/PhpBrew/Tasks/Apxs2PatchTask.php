<?php
namespace PhpBrew\Tasks;

use RuntimeException;
use PhpBrew\Utils;
use PhpBrew\Patch\PatchCollection;

class Apxs2PatchTask extends BaseTask
{
    public function patch($build, $options)
    {
        $this->logger->info('===> Applying patch - apxs2 module version name ...');

        if ($options->dryrun) {
            return;
        }

        $patches = PatchCollection::createPatchesForApxs2($this->logger, $build);
        foreach ($patches as $patch) {
            $patch->enableBackup();
            $patch->apply();
        }
    }
}

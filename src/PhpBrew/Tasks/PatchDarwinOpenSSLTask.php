<?php
namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\Utils;
use PhpBrew\Patch\PatchCollection;

class PatchDarwinOpenSSLTask extends BaseTask
{
    public function match(Build $build)
    {
        return PHP_OS === "Darwin" && version_compare(php_uname('r'), '15.0.0') > 0;
    }

    public function patch(Build $build)
    {
        $this->info("===> Checking if it's using openssl on darwin platform...");
        if ($this->match($build)) {
            $this->info("===> Applying patch file for openssl...");
            if (!$this->options->dryrun) {
                $patches = PatchCollection::createPatchesForOSXOpenssl($this->logger, $build);
                foreach ($patches as $patch) {
                    $patch->enableBackup();
                    $patch->apply();
                }
            }
        }
    }
}

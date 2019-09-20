<?php

namespace PhpBrew\Patches;

use PhpBrew\Buildable;
use PhpBrew\PatchKit\Patch;
use PhpBrew\PatchKit\DiffPatchRule;
use CLIFramework\Logger;

class PHP56WithOpenSSL11Patch extends Patch
{
    public function desc()
    {
        return 'php5.6 with openssl 1.1.x patch.';
    }

    public function match(Buildable $build, Logger $logger)
    {
        return version_compare($build->getVersion(), '5.6') === 0
            && version_compare($build->getVersion(), '5.6.31') >= 0 // patch only works for 5.6.31 and up
            && $build->isEnabledVariant('openssl');
    }

    public function rules()
    {
        $rules = array();
        $rules[] = DiffPatchRule::from('https://patch-diff.githubusercontent.com/raw/php/php-src/pull/2667.patch')
            ->strip(1)
            ->sha256('507bb74b2612328cdf5e59b964645a49d0f9367add001ecd698e0fd0d4445421');

        return $rules;
    }
}

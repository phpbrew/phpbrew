<?php

namespace PhpBrew\Patches;

use CLIFramework\Logger;
use PhpBrew\Buildable;
use PhpBrew\PatchKit\DiffPatchRule;
use PhpBrew\PatchKit\Patch;

class PHP53Patch extends Patch
{
    public function desc()
    {
        return 'php5.3.29 multi-sapi patch.'; // use generic patch description when there are more than one 5.3 patches
    }

    public function match(Buildable $build, Logger $logger)
    {
        // todo: not sure if this works for linux?
        return $build->osName === 'Darwin' && version_compare($build->getVersion(), '5.3.29') === 0;
    }

    public function rules()
    {
        $rules = array();
        // The patch only works for php5.3.29
        $rules[] = DiffPatchRule::from(
            GistContent::url(
                'javian',
                'bfcbd5bc5874ee9c539fb3d642cdce3e',
                'multi-sapi-5.3.29-homebrew.patch',
                'bf079cc68ec76290f02f57981ae85b20a06dd428',
            )
        )
            ->strip(1)
            ->sha256('3c3157bc5c4346108a398798b84dbbaa13409c43d3996bea2ddacb3277e0cee2');

        return $rules;
    }
}

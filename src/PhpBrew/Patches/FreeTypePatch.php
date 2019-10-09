<?php

namespace PhpBrew\Patches;

use PhpBrew\Buildable;
use PhpBrew\PatchKit\DiffPatchRule;
use PhpBrew\PatchKit\Patch;
use CLIFramework\Logger;

/**
 * As of recently, freetype-config has been removed in favor of pkg-config. It has been fixed in PHP 7.4beta1
 * but the older PHP sources need to be patched in order to be able to compile them on newer Ubuntu/Debian
 * distributions.
 */
class FreeTypePatch extends Patch
{
    public function desc()
    {
        return 'replace freetype-config with pkg-config on php older than 7.4';
    }

    public function match(Buildable $build, Logger $logger)
    {
        return $build->isEnabledVariant('gd') && version_compare($build->getVersion(), '7.4', '<=');
    }

    public function rules()
    {
        return array(
            DiffPatchRule::from(
                'https://git.archlinux.org/svntogit/packages.git/plain/trunk/freetype.patch?h=packages/php'
            )
                ->strip(1)
                ->sha256('07c4648669dc05afc3c1ad5a4739768079c423b817eabf5296ca3d1ea5ffd163')
        );
    }
}

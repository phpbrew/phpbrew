<?php
namespace PhpBrew\Patch;

use PhpBrew\Buildable;
use CLIFramework\Logger;

/**
 * Aggregates pathes.
 */
class PatchCollection
{
    public static function createPatchesFor64BitSupport(Logger $logger, Buildable $build)
    {
        return array(
            new RegexpPatch(
                $logger,
                $build,
                array('Makefile'),
                array(
                    RegexpPatchRule::allOf(array('/^BUILD_/'), '/\$\(CC\)/', '$(CXX)'),
                    RegexpPatchRule::allOf(array('/^EXTRA_LIBS =/'), '/^(.*)$/', '$1 -lstdc++')
                )
            )
        );
    }

    public static function createPatchesForApxs2(Logger $logger, Buildable $build)
    {
        $rules = array(
            RegexpPatchRule::always(
                '#libphp\$\(PHP_MAJOR_VERSION\)\.#',
                'libphp$(PHP_VERSION).'
            ),
            RegexpPatchRule::always(
                '#libs/libphp\$PHP_MAJOR_VERSION\.#',
                'libs/libphp$PHP_VERSION.'
            ),
            RegexpPatchRule::always(
                '#libs/libphp[57].so#',
                'libs/libphp$PHP_VERSION.so'
            ),
            RegexpPatchRule::always(
                '#libs/libphp[57].la#',
                'libs/libphp$PHP_VERSION.la'
            ),
            RegexpPatchRule::always(
                '#libphp\$PHP_MAJOR_VERSION\.#',
                'libphp$PHP_VERSION.'
            )
        );
        return array(
            new RegexpPatch(
                $logger,
                $build,
                array('configure', 'Makefile.global'),
                $rules
            )
        );
    }
}

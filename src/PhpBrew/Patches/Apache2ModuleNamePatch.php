<?php

namespace PhpBrew\Patches;

use CLIFramework\Logger;
use PhpBrew\Buildable;
use PhpBrew\PatchKit\Patch;
use PhpBrew\PatchKit\RegExpPatchRule;

class Apache2ModuleNamePatch extends Patch
{
    public function desc()
    {
        return 'replace apache php module name with custom version name';
    }

    public function match(Buildable $build, Logger $logger)
    {
        return $build->isEnabledVariant('apxs2');
    }

    public function rules()
    {
        $rules = array();

        /*
        This is for replacing something like this:

        SAPI_SHARED=libs/libphp$PHP_MAJOR_VERSION.$SHLIB_DL_SUFFIX_NAME
        SAPI_STATIC=libs/libphp$PHP_MAJOR_VERSION.a
        SAPI_LIBTOOL=libphp$PHP_MAJOR_VERSION.la

        OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la

        OVERALL_TARGET=libs/libphp$PHP_MAJOR_VERSION.bundle

        SAPI_SHARED=libs/libphp5.so
        */
        $rules[] = RegExpPatchRule::files(array('configure'))
            ->always()
            ->replaces(
                '#libphp\$PHP_MAJOR_VERSION\.#',
                'libphp$PHP_VERSION.'
            );

        $rules[] = RegExpPatchRule::files(array('configure'))
            ->always()
            ->replaces(
                '#libs/libphp[57].(so|la)#',
                'libs/libphp\$PHP_VERSION.$1'
            );

        $rules[] = RegExpPatchRule::files(array('Makefile.global'))
            ->always()
            ->replaces(
                '#libphp\$\(PHP_MAJOR_VERSION\)#',
                'libphp$(PHP_VERSION)'
            );

        return $rules;
    }
}

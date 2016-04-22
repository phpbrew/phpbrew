<?php
namespace PhpBrew\Patch;

use PhpBrew\Buildable;
use CLIFramework\Logger;

/**
 * Aggregates pathes.
 */
class PatchCollection
{


    /**
     * @see https://github.com/phpbrew/phpbrew/issues/636
     */
    public static function createPatchesForOSXOpenssl(Logger $logger, Buildable $build)
    {
        /*
         Macports
         -lssl /opt/local/lib/libssl.dylib
         -lcrypto /opt/local/lib/libcrypto.dylib

         /usr/local/opt/openssl/lib/libssl.dylib 
         /usr/local/opt/openssl/lib/libcrypto.dylib
         */
        $dylibssl = null;
        $dylibcrypto = null;

        
        $paths = array('/opt/local/lib/libssl.dylib',
            '/usr/local/opt/openssl/lib/libssl.dylib',
            '/usr/local/lib/libssl.dylib', '/usr/lib/libssl.dylib');
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $dylibssl = $path;
                break;
            }
        }

        $paths = array('/opt/local/lib/libcrypto.dylib',
            '/usr/local/opt/openssl/lib/libcrypto.dylib',
            '/usr/local/lib/libcrypto.dylib', '/usr/lib/libcrypto.dylib');
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $dylibcrypto = $path;
                break;
            }
        }

        $rules = array();
        if ($dylibssl) {
            $rules[] = RegexpPatchRule::allOf(array('/^EXTRA_LIBS =/'), '/-lssl/', $dylibssl);
        }
        if ($dylibcrypto) {
            $rules[] = RegexpPatchRule::allOf(array('/^EXTRA_LIBS =/'), '/-lcrypto/', $dylibcrypto);
        }
        if (empty($rules)) {
            return array();
        }
        return array(new RegexpPatch($logger, $build, array('Makefile'), $rules));
    }


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

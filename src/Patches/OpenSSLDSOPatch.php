<?php

namespace PHPBrew\Patches;

use CLIFramework\Logger;
use PHPBrew\Buildable;
use PHPBrew\PatchKit\Patch;
use PHPBrew\PatchKit\RegExpPatchRule;

class OpenSSLDSOPatch extends Patch
{
    public function desc()
    {
        return 'openssl dso linking patch';
    }

    public function match(Buildable $build, Logger $logger)
    {
        return $build->osName === 'Darwin'
            && version_compare($build->osRelease, '15.0.0') > 0
            && $build->isEnabledVariant('openssl');
    }

    public function rules()
    {
        /*
        Macports
         -lssl /opt/local/lib/libssl.dylib
         -lcrypto /opt/local/lib/libcrypto.dylib

        HomeBrew
         /usr/local/opt/openssl/lib/libssl.dylib
         /usr/local/opt/openssl/lib/libcrypto.dylib
        */
        $dylibssl = null;
        $dylibcrypto = null;

        $paths = array('/opt/local/lib/libssl.dylib',
            '/usr/local/opt/openssl/lib/libssl.dylib',
            '/usr/local/lib/libssl.dylib', '/usr/lib/libssl.dylib', );
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $dylibssl = $path;
                break;
            }
        }

        $paths = array('/opt/local/lib/libcrypto.dylib',
            '/usr/local/opt/openssl/lib/libcrypto.dylib',
            '/usr/local/lib/libcrypto.dylib', '/usr/lib/libcrypto.dylib', );
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $dylibcrypto = $path;
                break;
            }
        }

        $rules = array();
        if ($dylibssl) {
            $rules[] = RegExpPatchRule::files('Makefile')
                ->allOf(array('/^EXTRA_LIBS =/'))
                ->replaces('/-lssl/', $dylibssl);
        }
        if ($dylibcrypto) {
            $rules[] = RegExpPatchRule::files('Makefile')
                ->allOf(array('/^EXTRA_LIBS =/'))
                ->replaces('/-lcrypto/', $dylibcrypto);
        }

        return $rules;
    }
}

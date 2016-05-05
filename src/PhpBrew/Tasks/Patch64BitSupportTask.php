<?php
namespace PhpBrew\Tasks;

use PhpBrew\Build;
use PhpBrew\Utils;
use PhpBrew\Patch\PatchCollection;

class Patch64BitSupportTask extends BaseTask
{

    /**
     * This seems also related to 5.4 when 'intl' is enabled: --enable-intl
     *
     * This is due to intl/msgformat/msgformat_helpers.cpp being a C++ file and
     * GCC not handling that case cleanly. The exact error is specifically due
     * to GCC not linking to libstdc++. Which is, actually, kinda reasonable
     * since it's been invoked as a plain C compiler. Anyway, you can get
     * around the problem for now by adding
     * "/usr/lib/gcc/i686-apple-darwin9/4.2.1/libstdc++.dylib" (if you're
     * building with gcc-4.2) or
     * "/usr/lib/gcc/i686-apple-darwin9/4.0.1/libstdc++.dylib" (if you're
     * building with gcc-4.0, the default) to your LDFLAGS. That's right,
     * WITHOUT -l or -L. I wouldn't consider this a real solution, but a better
     * solution is pending further research into the subject.
     *
     * This fixes the build error:
     *      --------------
     *      Undefined symbols:
     *      "___gxx_personality_v0", referenced from:
     *          EH_frame1 in msgformat_helpers.o
     *      ld: symbol(s) not found
     *      collect2: ld returned 1 exit status
     *      make: *** [sapi/cgi/php-cgi] Error 1
     *
     * https://bugs.php.net/bug.php?id=48795
     * https://blog.gcos.me/2012-10-19_how-to-compile-php53-on-64bit-linux-macos.html
     *
     * Related Platform:
     *
     * - Ubuntu 11.04 http://www.serverphorums.com/read.php?7,369479
     * - Ubuntu 14.04 https://github.com/phpbrew/phpbrew/issues/707
     * - Ubuntu 12.04
     * - CentOS 7 x86_64
     * - OS X 10.5
     * - OS X 10.6
     *
     */
    public function match(Build $build)
    {
        // Extract version from something like "php-5.3.2..."
        $currentVersion = preg_replace('/[^\d]*(\d+).(\d+).*/i', '$1.$2', $build->getVersion());
        return ($build->hasVariant('intl') && version_compare($currentVersion, '5.4', '<='));
    }

    public function patch(Build $build)
    {
        if ($this->match($build)) {
            // Then patch Makefile for PHP 5.3.x on 64bit system.
            $this->info("===> Applying patch file for php5.3.x on 64bit machine when intl is enabled.");
            if (!$this->options->dryrun) {
                $patches = PatchCollection::createPatchesFor64BitSupport($this->logger, $build);
                foreach ($patches as $patch) {
                    $patch->enableBackup();
                    $patch->apply();
                }
            }
        }
    }
}

<?php
namespace PhpBrew\Tasks;

use RuntimeException;
use PhpBrew\Utils;

class Apxs2PatchTask extends BaseTask
{
    public function patch($build, $options)
    {
        $this->logger->info('===> Applying patch - apxs2 module version name ...');

        if ($options->dryrun) {
            return;
        }

        // patch for libphp$(PHP_MAJOR_VERSION).so
        $patch=<<<'EOS'
perl -i.bak -pe 's#
libphp\$\(PHP_MAJOR_VERSION\)\.#libphp\$\(PHP_VERSION\)\.#gx' configure Makefile.global
EOS;
        $this->applyPatch($patch);

        $patch=<<<'EOS'
perl -i.bak -pe 's#
libs/libphp\$PHP_MAJOR_VERSION\.
#libs/libphp\$PHP_VERSION\.#gx' configure Makefile.global
EOS;
        $this->applyPatch($patch);

        // replace .so files
        $patch=<<<'EOS'
perl -i.bak -pe 's#
libs/libphp5.so
#libs/libphp\$PHP_VERSION\.so#gx' configure Makefile.global
EOS;
        $this->applyPatch($patch);

        // patch for OVERALL_TARGET=libphp$PHP_MAJOR_VERSION.la
        // libphp$(PHP_VERSION).la:
        // replace .la files
        $patch=<<<'EOS'
perl -i.bak -pe 's#
libs/libphp5.la
#libs/libphp\$PHP_VERSION\.la#gx' configure Makefile.global
EOS;
        $this->applyPatch($patch);

        $patch=<<<'EOS'
perl -i.bak -pe 's#
libphp\$PHP_MAJOR_VERSION\.#libphp\$PHP_VERSION\.#gx' configure Makefile.global
EOS;
        $this->applyPatch($patch);
    }

    private function applyPatch($patch)
    {
        try {
            Utils::system($patch);
        } catch (Exception $e) {
            throw new RuntimeException('apxs2 patch failed.', 0, $e);
        }
    }
}

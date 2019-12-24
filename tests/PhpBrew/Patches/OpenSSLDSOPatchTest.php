<?php

namespace PhpBrew\Tests\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\OpenSSLDSOPatch;
use PhpBrew\Testing\PatchTestCase;

/**
 * @small
 */
class OpenSSLDSOPatchTest extends PatchTestCase
{
    public function testPatch()
    {
        if (PHP_OS !== "Darwin") {
            $this->markTestSkipped('openssl DSO patch test only runs on darwin platform');
        }

        $logger = new Logger();
        $logger->setQuiet();

        $fromVersion = '5.5.17';
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        $this->setupBuildDirectory($fromVersion);

        $build = new Build($fromVersion);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('openssl');
        $this->assertTrue($build->isEnabledVariant('openssl'));

        $patch = new OpenSSLDSOPatch();
        $matched = $patch->match($build, $logger);
        $this->assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);
        $this->assertEquals(10, $patchedCount);
    }
}

<?php

namespace PHPBrew\Tests\Patches;

use CLIFramework\Logger;
use PHPBrew\Build;
use PHPBrew\Patches\OpenSSLDSOPatch;
use PHPBrew\Testing\PatchTestCase;

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
        $sourceFixtureDirectory = getenv('PHPBREW_FIXTURES_PHP_DIR') . DIRECTORY_SEPARATOR . $fromVersion;
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        $this->setupBuildDirectory($fromVersion);

        $build = new Build($fromVersion);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('openssl');
        $this->assertTrue($build->hasVariant('openssl'), 'openssl enabled');


        $patch = new OpenSSLDSOPatch();
        $matched = $patch->match($build, $logger);
        $this->assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);
        $this->assertEquals(10, $patchedCount);
    }
}

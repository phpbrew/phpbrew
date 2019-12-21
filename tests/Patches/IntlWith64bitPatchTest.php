<?php

namespace PHPBrew\Tests\Patches;

use CLIFramework\Logger;
use PHPBrew\Build;
use PHPBrew\Patches\IntlWith64bitPatch;
use PHPBrew\Testing\PatchTestCase;

class IntlWith64bitPatchTest extends PatchTestCase
{
    public function testPatch()
    {
        $logger = new Logger();
        $logger->setQuiet();

        $fromVersion = '5.3.29';
        $sourceFixtureDirectory = getenv('PHPBREW_FIXTURES_PHP_DIR') . DIRECTORY_SEPARATOR . $fromVersion;
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        if (!is_dir($sourceDirectory)) {
            $this->markTestSkipped("$sourceDirectory does not exist.");
        }

        // Copy the source Makefile to the Makefile
        // copy($sourceFixtureDirectory . '/Makefile', $sourceDirectory . '/Makefile');
        $this->setupBuildDirectory($fromVersion);

        $build = new Build($fromVersion);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('intl');
        $this->assertTrue($build->hasVariant('intl'), 'intl enabled');

        $patch = new IntlWith64bitPatch();
        $matched = $patch->match($build, $logger);
        $this->assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);
        $this->assertEquals(3, $patchedCount);

        $sourceExpectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . $fromVersion;
        $this->assertFileEquals($sourceExpectedDirectory . '/Makefile', $sourceDirectory . '/Makefile');
    }
}

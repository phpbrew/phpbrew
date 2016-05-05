<?php
use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\IntlWith64bitPatch;
use PhpBrew\Patches\Apache2ModuleNamePatch;
use PhpBrew\Utils;
use PhpBrew\Testing\PatchTestCase;

/**
 * @small
 */
class Apache2ModuleNamePatchTest extends PatchTestCase
{
    public function testPatch()
    {
        $logger = new Logger();
        $logger->setQuiet();

        $fromVersion = '5.5.17';
        $sourceFixtureDirectory = getenv('PHPBREW_FIXTURES_PHP_DIR') . DIRECTORY_SEPARATOR . $fromVersion;
        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        if (!is_dir($sourceDirectory)) {
            return $this->markTestSkipped("$sourceDirectory does not exist.");
        }

        $this->setupBuildDirectory($fromVersion);

        $build = new Build($fromVersion);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('apxs2');
        $this->assertTrue($build->hasVariant('apxs2'), 'apxs2 enabled');

        $patch = new Apache2ModuleNamePatch;
        $matched = $patch->match($build, $logger);
        $this->assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);
        $this->assertEquals(107, $patchedCount);

        $sourceExpectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . '5.5.17-apxs-patch';
        $this->assertFileEquals($sourceExpectedDirectory. '/Makefile.global', $sourceDirectory . '/Makefile.global');
        $this->assertFileEquals($sourceExpectedDirectory. '/configure', $sourceDirectory . '/configure');
    }
}

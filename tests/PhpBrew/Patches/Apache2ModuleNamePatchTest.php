<?php

namespace PhpBrew\Tests\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\Apache2ModuleNamePatch;
use PhpBrew\Testing\PatchTestCase;

/**
 * @small
 */
class Apache2ModuleNamePatchTest extends PatchTestCase
{

    public function versionProvider()
    {
        return array(
            array('5.5.17', 107, '/Makefile.global'),
            array('7.4.0', 25, '/build/Makefile.global')
        );
    }

    /**
     * @dataProvider versionProvider
     */
    public function testPatchVersion($version, $expectedPatchedCount, $makefile)
    {
        $logger = new Logger();
        $logger->setQuiet();

        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        if (!is_dir($sourceDirectory)) {
            $this->markTestSkipped("$sourceDirectory does not exist.");
        }

        $this->setupBuildDirectory($version);

        $build = new Build($version);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('apxs2');
        $this->assertTrue($build->isEnabledVariant('apxs2'));

        $patch = new Apache2ModuleNamePatch($version);
        $matched = $patch->match($build, $logger);
        $this->assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);

        $sourceExpectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . $version . '-apxs-patch';
        $this->assertEquals($expectedPatchedCount, $patchedCount);
        $this->assertFileEquals($sourceExpectedDirectory . $makefile, $sourceDirectory . $makefile);
        $this->assertFileEquals($sourceExpectedDirectory . '/configure', $sourceDirectory . '/configure');
    }
}

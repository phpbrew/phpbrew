<?php
use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\IntlWith64bitPatch;
use PhpBrew\Patches\Apache2ModuleNamePatch;
use PhpBrew\Patches\OpenSSLDSOPatch;
use PhpBrew\Utils;
use PhpBrew\Testing\PatchTestCase;

/**
 * @small
 */
class OpenSSLDSOPatchTest extends PatchTestCase
{
    public function testPatch()
    {
        if (PHP_OS !== "Darwin") {
            return $this->markTestSkipped('openssl DSO patch test only runs on darwin platform');
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


        $patch = new OpenSSLDSOPatch;
        $matched = $patch->match($build, $logger);
        $this->assertTrue($matched, 'patch matched');
        $patchedCount = $patch->apply($build, $logger);
        $this->assertEquals(10, $patchedCount);
        /*
        We can't assume the file equals because the test may be run on different platform and openssl may be installed 
        into different locations.

        $sourceExpectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . '5.5.17-openssl-dso-patch';
        $this->assertFileEquals($sourceExpectedDirectory. '/Makefile', $sourceDirectory . '/Makefile');
         */
    }
}

<?php

namespace PHPBrew\Patches;

use CLIFramework\Logger;
use PHPBrew\Build;
use PHPBrew\Testing\PatchTestCase;

class FreeTypePatchTest extends PatchTestCase
{
    public function testPatch()
    {
        $logger = new Logger();
        $logger->setQuiet();

        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        $this->setupBuildDirectory('7.3.12');

        $build = new Build('7.3.12');
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('gd');

        $patch = new FreeTypePatch();
        $this->assertTrue($patch->match($build, $logger));
        $this->assertGreaterThan(0, $patch->apply($build, $logger));

        $expectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . DIRECTORY_SEPARATOR . '7.3.12-freetype-patch';
        $this->assertFileEquals($expectedDirectory . '/ext/gd/config.m4', $sourceDirectory . '/ext/gd/config.m4');
    }
}

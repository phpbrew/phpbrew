<?php

namespace PhpBrew\Tests\Patches;

use CLIFramework\Logger;
use PhpBrew\Build;
use PhpBrew\Patches\PHP56WithOpenSSL11Patch;
use PhpBrew\Testing\PatchTestCase;

class PHP56WithOpenSSL11PatchTest extends PatchTestCase
{
    /**
     * @dataProvider versionProvider
     */
    public function testPatchVersion($version)
    {
        $logger = new Logger();
        $logger->setQuiet();

        $sourceDirectory = getenv('PHPBREW_BUILD_PHP_DIR');

        $this->setupBuildDirectory($version);

        $build = new Build($version);
        $build->setSourceDirectory($sourceDirectory);
        $build->enableVariant('openssl');
        $this->assertTrue($build->isEnabledVariant('openssl'));

        $patch = new PHP56WithOpenSSL11Patch();
        $this->assertTrue($patch->match($build, $logger));

        $this->assertGreaterThan(0, $patch->apply($build, $logger));

        $expectedDirectory = getenv('PHPBREW_EXPECTED_PHP_DIR') . '/' . $version . '-php56-openssl11-patch';

        foreach (
            array(
            'ext/openssl/openssl.c',
            'ext/openssl/xp_ssl.c',
            'ext/phar/util.c',
                 ) as $path
        ) {
            $this->assertFileEquals(
                $expectedDirectory . '/' .  $path,
                $sourceDirectory . '/' . $path
            );
        }
    }

    public static function versionProvider()
    {
        return array(
            array('5.6.40'),
        );
    }
}

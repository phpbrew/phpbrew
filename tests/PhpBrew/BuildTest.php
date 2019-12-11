<?php

namespace PhpBrew\Tests;

use PhpBrew\Build;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class BuildTest extends TestCase
{
    public function testBuildAPI()
    {
        $build = new Build('5.3.1');

        $build->setVersion('5.3.1');
        $build->enableVariant('debug');
        $build->enableVariant('icu');
        $build->enableVariant('sqlite');

        $build->disableVariant('sqlite');
        $build->disableVariant('mysql');
        $build->resolveVariants();

        $this->assertSame(1, $build->compareVersion('5.3.0'));
        $this->assertSame(1, $build->compareVersion('5.3'));
        $this->assertSame(-1, $build->compareVersion('5.4.0'));
        $this->assertSame(-1, $build->compareVersion('5.4'));
        $this->assertSame('php-5.3.1-debug-icu-dev', $build->getIdentifier());
    }

    public function testNeutralVirtualVariant()
    {
        $build = new Build('5.5.0');
        $build->setVersion('5.5.0');
        $build->enableVariant('neutral');
        $build->resolveVariants();

        $this->assertTrue($build->hasVariant('neutral'));
    }
}

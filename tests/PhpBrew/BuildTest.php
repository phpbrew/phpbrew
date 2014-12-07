<?php

/**
 * @small
 */
class BuildTest extends PHPUnit_Framework_TestCase
{
    public function testBuildAPI()
    {
        $build = new PhpBrew\Build('5.3.1');

        $build->setVersion('5.3.1');
        $build->enableVariant('debug');
        $build->enableVariant('icu');
        $build->enableVariant('sqlite');

        $build->disableVariant('sqlite');
        $build->disableVariant('mysql');
        $build->resolveVariants();

        Same( 1 , $build->compareVersion('5.3.0') );
        Same( 1 , $build->compareVersion('5.3') );
        Same( -1 , $build->compareVersion('5.4.0') );
        Same( -1 , $build->compareVersion('5.4') );
        Same('php-5.3.1-debug-icu-dev', $build->getIdentifier());
    }

    public function testNeutralVirtualVariant()
    {
        $build = new PhpBrew\Build('5.5.0');
        $build->setVersion('5.5.0');
        $build->enableVariant('neutral');
        $build->resolveVariants();

        $this->assertTrue($build->hasVariant('neutral') );
    }
}

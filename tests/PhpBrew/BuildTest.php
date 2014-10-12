<?php

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

        is( 1 , $build->compareVersion('5.3.0') );
        is( 1 , $build->compareVersion('5.3') );
        is( -1 , $build->compareVersion('5.4.0') );
        is( -1 , $build->compareVersion('5.4') );

        $id = $build->getIdentifier();
        is('php-5.3.1-debug-icu-dev',$id);
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

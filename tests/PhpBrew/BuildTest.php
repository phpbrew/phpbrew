<?php

class BuildTest extends PHPUnit_Framework_TestCase
{
    public function testBuildAPI()
    {
        $build = new PhpBrew\Build('5.3.1');
        ok($build);

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
        ok($id);
        is('php-5.3.1-debug-icu-dev',$id);
    }
}


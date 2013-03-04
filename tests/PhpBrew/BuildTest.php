<?php

class BuildTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $build = new PhpBrew\Build;

        $build->setVersion('5.3.0');
        $build->enableVariant('debug');
        $build->enableVariant('icu');
        $build->enableVariant('sqlite');

        $build->disableVariant('sqlite');
        $build->disableVariant('mysql');
        $build->resolveVariants();



        $id = $build->getIdentifier();
        ok($id);
        is('php-5.3.0-debug-icu-dev',$id);
    }
}


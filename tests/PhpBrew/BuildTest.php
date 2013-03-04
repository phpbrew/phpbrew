<?php

class BuildTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $build = new PhpBrew\Build;

        $build->setVersion('5.3.0');
        $build->addVariant('debug');
        $build->addVariant('icu');

        $id = $build->getIdentifier();
        ok($id);
        echo $id;
    }
}


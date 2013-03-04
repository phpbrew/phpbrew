<?php

class VariantBuilderTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $vbuilder = new PhpBrew\VariantBuilder;
        ok($vbuilder);
    }
}


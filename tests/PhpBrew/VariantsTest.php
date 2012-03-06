<?php

class VariantsTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $v = new PhpBrew\Variants;
        ok( $v );
        $v->enable('pdo');
        $v->enable('mysql');

        $options = $v->build();
        ok( in_array( '--enable-pdo' , $options ) );
        ok( $v->getVariantNames() );
    }
}


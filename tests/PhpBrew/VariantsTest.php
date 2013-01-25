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

        $v = new PhpBrew\Variants;
        ok( $v );
        $v->enable('default');
        $v->enable('sqlite');

        $options = $v->build();
        ok( in_array( '--enable-pdo' , $options ) );
        ok( in_array( '--with-pdo-sqlite' , $options ) );
        ok( $v->getVariantNames() );

    }
}


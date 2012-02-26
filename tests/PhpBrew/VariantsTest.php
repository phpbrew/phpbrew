<?php

class VariantsTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $v = new PhpBrew\Variants;
        ok( $v );
        $v->useFeature('pdo');
        $v->useFeature('mysql');

        $options = $v->build();
        ok( in_array( '--enable-pdo' , $options ) );
        ok( $v->getVariantNames() );
    }
}


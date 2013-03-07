<?php

class VariantBuilderTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $variants = new PhpBrew\VariantBuilder;
        ok($variants);

        $build = new PhpBrew\Build;
        $build->setVersion('5.3.0');
        $build->enableVariant('debug');
        $build->enableVariant('icu');
        $build->enableVariant('sqlite');
        $build->enableVariant('xml_all');
        $build->enableVariant('apxs2','/opt/local/apache2/apxs2');

        $build->disableVariant('sqlite');
        $build->disableVariant('mysql');
        $build->resolveVariants();

        $options = $variants->build($build);
        ok( in_array('--enable-debug',$options) );
        ok( in_array('--enable-libxml',$options) );
        ok( in_array('--enable-simplexml',$options) );

        ok( in_array('--with-apxs2=/opt/local/apache2/apxs2',$options) );

        ok( in_array('--without-sqlite3',$options) );
        ok( in_array('--without-mysql',$options) );
        ok( in_array('--without-mysqli',$options) );
    }
}


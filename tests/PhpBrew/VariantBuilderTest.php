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
        ok( in_array('--disable-all',$options) );
    }

    public function testMysqlPdoVariant()
    {
        $variants = new PhpBrew\VariantBuilder;
        ok($variants);

        $build = new PhpBrew\Build;
        $build->setVersion('5.3.0');
        $build->enableVariant('pdo');
        $build->enableVariant('mysql');
        $build->enableVariant('sqlite');
        $build->resolveVariants();

        $options = $variants->build($build);
        ok( in_array('--enable-pdo',$options) );
        ok( in_array('--with-mysql=mysqlnd',$options) );
        ok( in_array('--with-mysqli=mysqlnd',$options) );
        ok( in_array('--with-pdo-mysql=mysqlnd',$options) );
        ok( in_array('--with-pdo-sqlite',$options) );
    }


    public function testAllVariant()
    {
        $variants = new PhpBrew\VariantBuilder;
        ok($variants);

        $build = new PhpBrew\Build;
        $build->setVersion('5.3.0');
        $build->enableVariant('all');
        $build->disableVariant('mysql');
        $build->disableVariant('apxs2');
        $build->resolveVariants();

        $options = $variants->build($build);
        ok( in_array('--enable-all',$options) );
        ok( in_array('--without-apxs2',$options) );
        ok( in_array('--without-mysql',$options) );
    }

    /**
     * A test case for `neutral' virtual variant.
     */
    public function testNeutralVirtualVariant()
    {
        $variants = new PhpBrew\VariantBuilder;
        ok($variants);

        $build = new PhpBrew\Build;
        $build->setVersion('5.3.0');
        $build->enableVariant('neutral');
        $build->resolveVariants();

        $options = $variants->build($build);
        // ignore `--with-libdir` because this option should be set depending on client environments.
        $actual = array_filter($options, function($option) {
            return !preg_match("/^--with-libdir/", $option);
        });

        is( array(), $actual );
    }
}


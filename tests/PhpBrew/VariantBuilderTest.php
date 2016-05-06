<?php
use PhpBrew\VariantBuilder;
use PhpBrew\Build;

/**
 * @small
 */
class VariantBuilderTest extends PHPUnit_Framework_TestCase
{
    public function variantOptionProvider()
    {
        return array(
            array(array('debug'), '#--enable-debug#'),
            array(array('mysql'), '#--with-mysqli#'),
            array(array('intl'), '#--enable-intl#'),
            array(array('apxs2'), '#--with-apxs2=\S+#'),
            array(array('sqlite'), '#--with-sqlite#'),
            array(array('sqlite', 'pdo'), '#--with-pdo-sqlite#'),
            array(array('mysql', 'pdo'), '#--with-pdo-mysql#'),
            array(array('pgsql', 'pdo'), '#--with-pdo-pgsql#'),
            array(array('default'), '#..#'),
            array(array('mcrypt'), '#--with-mcrypt=#'),
            array(array('openssl'), '#--with-openssl=#'),
            array(array('zlib'), '#--with-zlib=#'),
            array(array('curl'), '#--with-curl=#'),
        );
    }


    /**
     * @dataProvider variantOptionProvider
     */
    public function testVariantOption(array $variants, $optionPattern)
    {
        $build = new Build('5.5.0');

        foreach ($variants as $variant) {
            $k = explode('=', $variant, 2);
            if (count($k) == 2) {
                $build->enableVariant($k[0], $k[1]);
            } else {
                $build->enableVariant($k[0]);
            }
        }
        $build->resolveVariants();
        $variantBuilder = new VariantBuilder;
        $options = $variantBuilder->build($build);

        $patterns = (array) $optionPattern;
        foreach ($patterns as $p) {
            $this->assertNotEmpty(preg_grep($p, $options));
        }
    }

    public function test()
    {
        $variants = new VariantBuilder;
        $build = new Build('5.3.0');
        $build->enableVariant('debug');
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

    public function testEverything()
    {
        $variants = new PhpBrew\VariantBuilder;
        ok($variants);

        $build = new PhpBrew\Build('5.6.0');
        $build->enableVariant('everything');
        $build->disableVariant('openssl');
        $build->resolveVariants();

        $options = $variants->build($build);

        $this->assertNotContains('--enable-all', $options);
        $this->assertNotContains('--with-apxs2=/usr/bin/apxs', $options);
        $this->assertNotContains('--with-openssl=/usr', $options);
    }


    public function testMysqlPdoVariant()
    {
        $variants = new PhpBrew\VariantBuilder;
        ok($variants);

        $build = new PhpBrew\Build('5.3.0');
        $build->enableVariant('pdo');
        $build->enableVariant('mysql');
        $build->enableVariant('sqlite');
        $build->resolveVariants();

        $options = $variants->build($build);
        $this->assertContains('--enable-pdo',$options);
        $this->assertContains('--with-mysql=mysqlnd',$options);
        $this->assertContains('--with-mysqli=mysqlnd',$options);
        $this->assertContains('--with-pdo-mysql=mysqlnd',$options);
        $this->assertContains('--with-pdo-sqlite',$options);
    }

    public function testAllVariant()
    {
        $variants = new VariantBuilder;
        $build = new Build('5.3.0');
        $build->enableVariant('all');
        $build->disableVariant('mysql');
        $build->disableVariant('apxs2');
        $build->resolveVariants();

        $options = $variants->build($build);
        $this->assertContains('--enable-all',$options);
        $this->assertContains('--without-apxs2',$options);
        $this->assertContains('--without-mysql',$options);
    }

    /**
     * A test case for `neutral' virtual variant.
     */
    public function testNeutralVirtualVariant()
    {
        $variants = new VariantBuilder;
        $build = new Build('5.3.0');
        // $build->setVersion('5.3.0');
        $build->enableVariant('neutral');
        $build->resolveVariants();

        $options = $variants->build($build);
        // ignore `--with-libdir` because this option should be set depending on client environments.
        $actual = array_filter($options, function ($option) {
            return !preg_match("/^--with-libdir/", $option);
        });
        $this->assertEquals(array(), $actual);
    }
}

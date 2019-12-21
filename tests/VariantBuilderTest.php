<?php

namespace PHPBrew\Tests;

use PHPBrew\Build;
use PHPBrew\VariantBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @small
 * @group macosIncompatible
 */
class VariantBuilderTest extends TestCase
{
    public function variantOptionProvider()
    {
        return array(
            array(array('debug'),  '#--enable-debug#'),
            array(array('intl'),   '#--enable-intl#'),
            array(array('apxs2'),  '#--with-apxs2#'),
            array(array('sqlite'), '#--with-sqlite#'),
            array(array('mysql'),  '#--with-mysqli#'),
            array(array('pgsql'),  '#--with-pgsql#'),
            array(array('xml'),    array('#--enable-(dom|libxml|simplexml)#', '#--with-libxml-dir#')),

            array(array('sqlite', 'pdo'), '#--with-pdo-sqlite#'),
            array(array('mysql', 'pdo'),  '#--with-pdo-mysql#'),
            array(array('pgsql', 'pdo'),  '#--with-pdo-pgsql#'),
            array(array('default'),       '#..#'),
            array(array('mcrypt'),        '#--with-mcrypt#'),
            array(array('openssl'),       '#--with-openssl#'),
            array(array('zlib'),          '#--with-zlib#'),
            array(array('curl'),          '#--with-curl#'),
            array(array('readline'),      '#--with-readline#'),
            array(array('editline'),      '#--with-libedit#'),
            array(array('gettext'),       '#--with-gettext#'),
            array(array('ipc'),           array('#--enable-shmop#','#--enable-sysvshm#')),
            array(array('gmp'),           '#--with-gmp#'),
            array(array('mhash'),         '#--with-mhash#'),
            array(array('iconv'),         '#--with-iconv#'),
            array(array('bz2'),           '#--with-bz2#'),
            array(array('gd'),            array('#--with-gd#', '#--with-png-dir#', '#--with-jpeg-dir#')),
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

            if (getenv('TRAVIS') && in_array($k[0], array("apxs2","gd","editline"))) {
                $this->markTestSkipped("Travis CI doesn't support {$k[0]}.");
            }

            if (count($k) == 2) {
                $build->enableVariant($k[0], $k[1]);
            } else {
                $build->enableVariant($k[0]);
            }
        }
        $build->resolveVariants();
        $variantBuilder = new VariantBuilder();
        $options = $variantBuilder->build($build);

        $patterns = (array) $optionPattern;
        foreach ($patterns as $p) {
            $this->assertNotEmpty(preg_grep($p, $options));
        }
    }

    public function test()
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        $build->enableVariant('debug');
        $build->enableVariant('sqlite');
        $build->enableVariant('xml_all');
        $build->enableVariant('apxs2', '/opt/local/apache2/apxs2');

        $build->disableVariant('sqlite');
        $build->disableVariant('mysql');
        $build->resolveVariants();
        $options = $variants->build($build);

        $this->assertContains('--enable-debug', $options);
        $this->assertContains('--enable-libxml', $options);
        $this->assertContains('--enable-simplexml', $options);

        $this->assertContains('--with-apxs2=/opt/local/apache2/apxs2', $options);

        $this->assertContains('--without-sqlite3', $options);
        $this->assertContains('--without-mysql', $options);
        $this->assertContains('--without-mysqli', $options);
        $this->assertContains('--disable-all', $options);
    }

    public function testEverything()
    {
        $variants = new VariantBuilder();

        $build = new Build('5.6.0');
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
        $variants = new VariantBuilder();

        $build = new Build('5.3.0');
        $build->enableVariant('pdo');
        $build->enableVariant('mysql');
        $build->enableVariant('sqlite');
        $build->resolveVariants();

        $options = $variants->build($build);
        $this->assertContains('--enable-pdo', $options);
        $this->assertContains('--with-mysql=mysqlnd', $options);
        $this->assertContains('--with-mysqli=mysqlnd', $options);
        $this->assertContains('--with-pdo-mysql=mysqlnd', $options);
        $this->assertContains('--with-pdo-sqlite', $options);
    }

    public function testAllVariant()
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        $build->enableVariant('all');
        $build->disableVariant('mysql');
        $build->disableVariant('apxs2');
        $build->resolveVariants();

        $options = $variants->build($build);
        $this->assertContains('--enable-all', $options);
        $this->assertContains('--without-apxs2', $options);
        $this->assertContains('--without-mysql', $options);
    }

    /**
     * A test case for `neutral' virtual variant.
     */
    public function testNeutralVirtualVariant()
    {
        $variants = new VariantBuilder();
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

    /**
     * @param string $version
     * @param string $expected
     *
     * @dataProvider libXmlProvider
     */
    public function testLibXml($version, $expected)
    {
        $build = new Build($version);
        $build->enableVariant('xml');

        $builder = new VariantBuilder();
        $options = $builder->build($build);

        $this->assertContains($expected, $options);
    }

    public static function libXmlProvider()
    {
        return array(
            array('7.3.0', '--enable-libxml'),

            // see https://github.com/php/php-src/pull/4037
            array('7.4.0-dev', '--with-libxml'),
        );
    }

    /**
     * @param string $version
     * @param string $expected
     *
     * @dataProvider zipProvider
     */
    public function testZip($version, $expected)
    {
        $build = new Build($version);
        $build->enableVariant('zip');

        $builder = new VariantBuilder();
        $options = $builder->build($build);

        $this->assertContains($expected, $options);
    }

    public static function zipProvider()
    {
        return array(
            array('7.3.0', '--enable-zip'),

            // see https://github.com/php/php-src/pull/4072
            array('7.4.0-dev', '--with-zip'),
        );
    }
}

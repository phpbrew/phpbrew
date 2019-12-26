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
            'apxs2' => array(
                array('apxs2'),
                array('--with-apxs2'),
            ),
            'bz2' => array(
                array('bz2'),
                array('--with-bz2'),
            ),
            'curl' => array(
                array('curl'),
                array('--with-curl'),
            ),
            'debug' => array(
                array('debug'),
                array('--enable-debug'),
            ),
            'editline' => array(
                array('editline'),
                array('--with-libedit'),
            ),
            'gd' => array(
                array('gd'),
                array(
                    '--with-gd',
                    '--with-png-dir',
                    '--with-jpeg-dir',
                ),
            ),
            'gettext' => array(
                array('gettext'),
                array('--with-gettext'),
            ),
            'gmp' => array(
                array('gmp'),
                array('--with-gmp'),
            ),
            'iconv' => array(
                array('iconv'),
                array('--with-iconv'),
            ),
            'intl' => array(
                array('intl'),
                array('--enable-intl'),
            ),
            'ipc' => array(
                array('ipc'),
                array(
                    '--enable-shmop',
                    '--enable-sysvshm',
                ),
            ),
            'mcrypt' => array(
                array('mcrypt'),
                array('--with-mcrypt'),
            ),
            'mhash' => array(
                array('mhash'),
                array('--with-mhash'),
            ),
            'mysql' => array(
                array('mysql'),
                array('--with-mysqli'),
            ),
            'openssl' => array(
                array('openssl'),
                array('--with-openssl'),
            ),
            'pdo-mysql' => array(
                array('mysql', 'pdo'),
                array('--with-pdo-mysql'),
            ),
            'pdo-pgsql' => array(
                array('pgsql', 'pdo'),
                array('--with-pdo-pgsql'),
            ),
            'pdo-sqlite' => array(
                array('sqlite', 'pdo'),
                array('--with-pdo-sqlite'),
            ),
            'pgsql' => array(
                array('pgsql'),
                array('--with-pgsql'),
            ),
            'readline' => array(
                array('readline'),
                array('--with-readline'),
            ),
            'sqlite' => array(
                array('sqlite'),
                array('--with-sqlite3'),
            ),
            'xml' => array(
                array('xml'),
                array(
                    '--enable-dom',
                    '--enable-libxml',
                    '--enable-simplexml',
                    '--with-libxml-dir',
                ),
            ),
            'zlib' => array(
                array('zlib'),
                array('--with-zlib'),
            ),
        );
    }

    /**
     * @dataProvider variantOptionProvider
     */
    public function testVariantOption(array $variants, $expectedOptions)
    {
        $build = new Build('5.5.0');
        foreach ($variants as $variant) {
            if (getenv('TRAVIS') && in_array($variant, array("apxs2", "gd", "editline"))) {
                $this->markTestSkipped("Travis CI doesn't support $variant}.");
            }

            $build->enableVariant($variant);
        }
        $build->resolveVariants();
        $variantBuilder = new VariantBuilder();
        $options = $variantBuilder->build($build)->getOptions();

        foreach ($expectedOptions as $expectedOption) {
            $this->assertArrayHasKey($expectedOption, $options);
        }
    }

    public function test()
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        $build->enableVariant('debug');
        $build->enableVariant('sqlite');
        $build->enableVariant('xml');
        $build->enableVariant('apxs2', '/opt/local/apache2/apxs2');

        $build->disableVariant('sqlite');
        $build->disableVariant('mysql');
        $build->resolveVariants();
        $options = $variants->build($build)->getOptions();

        $this->assertArrayHasKey('--enable-debug', $options);
        $this->assertArrayHasKey('--enable-libxml', $options);
        $this->assertArrayHasKey('--enable-simplexml', $options);

        $this->assertArrayHasKey('--with-apxs2', $options);
        $this->assertSame('/opt/local/apache2/apxs2', $options['--with-apxs2']);

        $this->assertArrayHasKey('--without-sqlite3', $options);
        $this->assertArrayHasKey('--without-mysql', $options);
        $this->assertArrayHasKey('--without-mysqli', $options);
        $this->assertArrayHasKey('--disable-all', $options);
    }

    public function testEverything()
    {
        $variants = new VariantBuilder();

        $build = new Build('5.6.0');
        $build->enableVariant('everything');
        $build->disableVariant('openssl');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();

        $this->assertArrayNotHasKey('--enable-all', $options);
        $this->assertArrayNotHasKey('--with-apxs2', $options);
        $this->assertArrayNotHasKey('--with-openssl', $options);
    }

    public function testMysqlPdoVariant()
    {
        $variants = new VariantBuilder();

        $build = new Build('5.3.0');
        $build->enableVariant('pdo');
        $build->enableVariant('mysql');
        $build->enableVariant('sqlite');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        $this->assertArrayHasKey('--enable-pdo', $options);
        $this->assertArrayHasKey('--with-mysql', $options);
        $this->assertSame('mysqlnd', $options['--with-mysql']);
        $this->assertArrayHasKey('--with-mysqli', $options);
        $this->assertSame('mysqlnd', $options['--with-mysqli']);
        $this->assertArrayHasKey('--with-pdo-mysql', $options);
        $this->assertSame('mysqlnd', $options['--with-pdo-mysql']);
        $this->assertArrayHasKey('--with-pdo-sqlite', $options);
    }

    public function testAllVariant()
    {
        $variants = new VariantBuilder();
        $build = new Build('5.3.0');
        $build->enableVariant('all');
        $build->disableVariant('mysql');
        $build->disableVariant('apxs2');
        $build->resolveVariants();

        $options = $variants->build($build)->getOptions();
        $this->assertArrayHasKey('--enable-all', $options);
        $this->assertArrayHasKey('--without-apxs2', $options);
        $this->assertArrayHasKey('--without-mysql', $options);
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

        $options = $variants->build($build)->getOptions();
        // ignore `--with-libdir` because this option should be set depending on client environments
        unset($options['--with-libdir']);

        $this->assertEquals(array(), $options);
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
        $options = $builder->build($build)->getOptions();

        $this->assertArrayHasKey($expected, $options);
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
        $options = $builder->build($build)->getOptions();

        $this->assertArrayHasKey($expected, $options);
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

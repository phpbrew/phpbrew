<?php
use PhpBrew\VariantParser;

/**
 * @small
 */
class VariantParserTest extends PHPUnit_Framework_TestCase
{
    public function makeArgs($arg)
    {
        return VariantParser::parseCommandArguments(preg_split('#\s+#', $arg));
    }

    public function test()
    {
        $info = $this->makeArgs('+pdo+sqlite+debug'
                . '+apxs=/opt/local/apache2/bin/apxs+calendar'
                . '-mysql'
                . ' -- --with-icu-dir /opt/local');

        $this->assertNotEmpty($info['enabled_variants']);
        $this->assertNotEmpty($info['disabled_variants']);

        $this->assertTrue($info['enabled_variants']['pdo']);
        $this->assertTrue($info['enabled_variants']['sqlite']);
        $this->assertTrue($info['disabled_variants']['mysql']);
        $this->assertEquals($info['extra_options'], array('--with-icu-dir', '/opt/local'));
    }

    public function testVariantAll()
    {
        $info = $this->makeArgs('+all -apxs2 -mysql');

        $this->assertNotEmpty($info['enabled_variants']);
        $this->assertNotEmpty($info['disabled_variants']);

        $this->assertTrue($info['enabled_variants']['all']);
        $this->assertTrue($info['disabled_variants']['mysql']);
        $this->assertTrue($info['disabled_variants']['apxs2']);
    }

    public function variantGroupOverloadProvider()
    {
        return array(
            array('+default +openssl=/usr', array('openssl' => '/usr')), // overrides default variant value
            array('+openssl=/usr +default', array('openssl' => '/usr')), // order must be irrelevant
            array('+default -openssl', array()), // negative variant
            array('-openssl +default', array()), // negative variant precedence
            array('+default -openssl=/usr', array()), // negative variant with an overridden value
        );
    }

    /**
     * @dataProvider variantGroupOverloadProvider
     */
    public function testVariantGroupOverload($arg, array $variant)
    {
        $info = $this->makeArgs($arg);
        $this->assertArraySubset($variant, $info['enabled_variants']);
    }

    /**
     * @link https://github.com/phpbrew/phpbrew/issues/495
     */
    public function testBug495()
    {
        $variants = $this->makeArgs('+gmp=/path/x86_64-linux-gnu -openssl-xdebug');
        $expected = array(
            'enabled_variants' => array(
                'gmp' => '/path/x86_64-linux-gnu'
            ),
            'disabled_variants' => array('openssl' => true, 'xdebug' => true)
        );
        $this->assertArraySubset($expected, $variants);
    }

    public function testVariantUserValueContainsVersion()
    {
        $variants = $this->makeArgs('+openssl=/usr/local/Cellar/openssl/1.0.2e +gettext=/usr/local/Cellar/gettext/0.19.7');
        $expected = array(
            'enabled_variants' => array(
                'openssl' => '/usr/local/Cellar/openssl/1.0.2e',
                'gettext' => '/usr/local/Cellar/gettext/0.19.7',
            ),
        );

        $this->assertArraySubset($expected, $variants);
    }
}

<?php
use PhpBrew\VariantParser;

/**
 * @small
 */
class VariantParserTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $arg = '+pdo+sqlite+debug'
                . '+apxs=/opt/local/apache2/bin/apxs+calendar'
                . '-mysql'
                . ' -- --with-icu-dir /opt/local';
        $args = preg_split('#\s+#',$arg);
        $info = VariantParser::parseCommandArguments($args);

        $this->assertNotEmpty($info['enabled_variants']);
        $this->assertNotEmpty($info['disabled_variants']);

        $this->assertTrue($info['enabled_variants']['pdo']);
        $this->assertTrue($info['enabled_variants']['sqlite']);
        $this->assertTrue($info['disabled_variants']['mysql']);
        $this->assertEquals($info['extra_options'], array('--with-icu-dir', '/opt/local'));
    }

    public function testVariantAll()
    {
        $arg = '+all -apxs2 -mysql';
        $args = preg_split('#\s+#',$arg);
        $info = VariantParser::parseCommandArguments($args);

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
        $args = preg_split('#\s+#',$arg);
        $info = VariantParser::parseCommandArguments($args);
        $this->assertArraySubset($variant, $info['enabled_variants']);
    }
}

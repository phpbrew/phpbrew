<?php

namespace PHPBrew\Tests;

use CLIFramework\Logger;
use PHPBrew\VariantParser;
use PHPUnit\Framework\TestCase;

class VariantParserTest extends TestCase
{
    public function test()
    {
        $this->assertEquals(array(
            'enabled_variants' => array(
                'pdo' => true,
                'sqlite' => true,
                'debug' => true,
                'apxs' => '/opt/local/apache2/bin/apxs',
                'calendar' => true,
            ),
            'disabled_variants' => array(
                'mysql' => true,
            ),
            'extra_options' => array(
                '--with-icu-dir',
                '/opt/local',
            ),
        ), $this->parse(array(
            '+pdo+sqlite+debug+apxs=/opt/local/apache2/bin/apxs+calendar-mysql',
            '--',
            '--with-icu-dir',
            '/opt/local',
        )));
    }

    public function testVariantAll()
    {
        $this->assertEquals(array(
            'enabled_variants' => array(
                'all' => true,
            ),
            'disabled_variants' => array(
                'apxs2' => true,
                'mysql' => true,
            ),
            'extra_options' => array(),
        ), $this->parse(array(
            '+all',
            '-apxs2',
            '-mysql',
        )));
    }

    /**
     * @dataProvider variantGroupOverloadProvider
     */
    public function testVariantGroupOverload(array $args, array $expectedEnabledVariants)
    {
        $info = $this->parse($args);
        $this->assertEquals($expectedEnabledVariants, $info['enabled_variants']);
    }

    public static function variantGroupOverloadProvider()
    {
        return array(
            'overrides default variant value' => array(
                array('+default', '+openssl=/usr'),
                array(
                    'default' => true,
                    'openssl' => '/usr',
                ),
            ),
            'order must be irrelevant' => array(
                array('+openssl=/usr', '+default'),
                array(
                    'openssl' => '/usr',
                    'default' => true,
                ),
            ),
            'negative variant' => array(
                array('+default', '-openssl'),
                array(
                    'default' => true,
                ),
            ),
            'negative variant precedence' => array(
                array('-openssl', '+default'),
                array(
                    'default' => true,
                ),
            ),
            'negative variant with an overridden value' => array(
                array('+default', '-openssl=/usr'),
                array(
                    'default' => true,
                ),
            ),
        );
    }

    /**
     * @link https://github.com/phpbrew/phpbrew/issues/495
     */
    public function testBug495()
    {
        $this->assertEquals(array(
            'enabled_variants' => array(
                'gmp' => '/path/x86_64-linux-gnu'
            ),
            'disabled_variants' => array(
                'openssl' => true,
                'xdebug' => true,
            ),
            'extra_options' => array(),
        ), $this->parse(array(
            '+gmp=/path/x86_64-linux-gnu',
            '-openssl-xdebug',
        )));
    }

    public function testVariantUserValueContainsVersion()
    {
        $this->assertEquals(array(
            'enabled_variants' => array(
                'openssl' => '/usr/local/Cellar/openssl/1.0.2e',
                'gettext' => '/usr/local/Cellar/gettext/0.19.7',
            ),
            'disabled_variants' => array(),
            'extra_options' => array(),
        ), $this->parse(array(
            '+openssl=/usr/local/Cellar/openssl/1.0.2e',
            '+gettext=/usr/local/Cellar/gettext/0.19.7',
        )));
    }

    /**
     * @dataProvider revealCommandArgumentsProvider
     */
    public function testRevealCommandArguments(array $info, $expected)
    {
        $this->assertEquals($expected, VariantParser::revealCommandArguments($info));
    }

    public static function revealCommandArgumentsProvider()
    {
        return array(
            array(
                array(
                    'enabled_variants' => array(
                        'mysql' => true,
                        'openssl' => '/usr',
                    ),
                    'disabled_variants' => array(
                        'apxs2' => true,
                    ),
                    'extra_options' => array(
                        '--with-icu-dir=/usr'
                    ),
                ),
                '+mysql +openssl=/usr -apxs2 -- --with-icu-dir=/usr',
            ),
        );
    }

    private function parse(array $args)
    {
        $logger = new Logger();
        $logger->setQuiet();

        return VariantParser::parseCommandArguments($args, $logger);
    }
}

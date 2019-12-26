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
                'pdo' => null,
                'sqlite' => null,
                'debug' => null,
                'apxs' => '/opt/local/apache2/bin/apxs',
                'calendar' => null,
            ),
            'disabled_variants' => array(
                'mysql' => null,
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
                'all' => null,
            ),
            'disabled_variants' => array(
                'apxs2' => null,
                'mysql' => null,
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
                    'default' => null,
                    'openssl' => '/usr',
                ),
            ),
            'order must be irrelevant' => array(
                array('+openssl=/usr', '+default'),
                array(
                    'openssl' => '/usr',
                    'default' => null,
                ),
            ),
            'negative variant' => array(
                array('+default', '-openssl'),
                array(
                    'default' => null,
                ),
            ),
            'negative variant precedence' => array(
                array('-openssl', '+default'),
                array(
                    'default' => null,
                ),
            ),
            'negative variant with an overridden value' => array(
                array('+default', '-openssl=/usr'),
                array(
                    'default' => null,
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
                'openssl' => null,
                'xdebug' => null,
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

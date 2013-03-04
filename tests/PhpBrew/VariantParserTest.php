<?php
use PhpBrew\VariantParser;

class VariantParserTest extends PHPUnit_Framework_TestCase
{


    public function argumentsProvider()
    {
        return array(
            array(
                preg_split('#\s+#',
                          '+pdo+sqlite+debug'
                        . '+apxs=/opt/local/apache2/bin/apxs+calendar'
                        . '-mysql'
                        . ' -- --with-icu-dir /opt/local'
                )
            )
        );
    }


    /**
     * @dataProvider argumentsProvider
     */
    public function test($args)
    {
        $info = VariantParser::parseCommandArguments($args);
        ok($info['enabled_variants']);
        ok($info['enabled_variants']['pdo']);
        ok($info['enabled_variants']['sqlite']);
        ok($info['disabled_variants']);
        ok($info['disabled_variants']['mysql']);
        ok($info['extra_options']);
    }
}


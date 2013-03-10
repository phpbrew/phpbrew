<?php
use PhpBrew\VariantParser;

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
        ok($info['enabled_variants']);
        ok($info['enabled_variants']['pdo']);
        ok($info['enabled_variants']['sqlite']);
        ok($info['disabled_variants']);
        ok($info['disabled_variants']['mysql']);
        ok($info['extra_options']);
    }

    public function testVariantAll()
    {
        $arg = '+all -apxs2 -mysql';
        $args = preg_split('#\s+#',$arg);
        $info = VariantParser::parseCommandArguments($args);
        ok($info['enabled_variants']);
        ok($info['enabled_variants']['all']);
        ok($info['disabled_variants']);
        ok($info['disabled_variants']['mysql']);
        ok($info['disabled_variants']['apxs2']);
    }
}


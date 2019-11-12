<?php

namespace PhpBrew\Tests\BuildSettings;

use PhpBrew\BuildSettings\DefaultBuildSettings;
use PHPUnit\Framework\TestCase;

class DefaultBuildSettingsTest extends TestCase
{
    public function testDefaultEnabledVariants()
    {
        $settings = new DefaultBuildSettings();
        not_ok($settings->isEnabledVariant('sqlite'));
        ok($settings->isEnabledVariant('bcmath'));
        ok($settings->isEnabledVariant('bz2'));
        ok($settings->isEnabledVariant('calendar'));
        ok($settings->isEnabledVariant('cli'));
        ok($settings->isEnabledVariant('ctype'));
        ok($settings->isEnabledVariant('dom'));
        ok($settings->isEnabledVariant('fileinfo'));
        ok($settings->isEnabledVariant('filter'));
        ok($settings->isEnabledVariant('ipc'));
        ok($settings->isEnabledVariant('json'));
        ok($settings->isEnabledVariant('mbregex'));
        ok($settings->isEnabledVariant('mbstring'));
        ok($settings->isEnabledVariant('mhash'));
        ok($settings->isEnabledVariant('pcntl'));
        ok($settings->isEnabledVariant('pcre'));
        ok($settings->isEnabledVariant('pdo'));
        ok($settings->isEnabledVariant('phar'));
        ok($settings->isEnabledVariant('posix'));
        ok($settings->isEnabledVariant('readline'));
        ok($settings->isEnabledVariant('sockets'));
        ok($settings->isEnabledVariant('tokenizer'));
        ok($settings->isEnabledVariant('xml'));
        ok($settings->isEnabledVariant('curl'));
        ok($settings->isEnabledVariant('zip'));
        ok($settings->isEnabledVariant('openssl'));
    }

    public function testAdditionalEnabledVariants()
    {
        $variants = array(
            'sqlite' => true
        );
        $settings = new DefaultBuildSettings(array('enabled_variants' => $variants));
        ok($settings->getVariant('sqlite'));
        ok($settings->isEnabledVariant('curl'));
    }
}

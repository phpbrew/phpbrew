<?php

namespace PhpBrew\Tests\BuildSettings;

use PhpBrew\BuildSettings\DefaultBuildSettings;
use PHPUnit\Framework\TestCase;

class DefaultBuildSettingsTest extends TestCase
{
    public function testDefaultEnabledVariants()
    {
        $settings = new DefaultBuildSettings();
        $this->assertFalse($settings->isEnabledVariant('sqlite'));
        $this->assertTrue($settings->isEnabledVariant('bcmath'));
        $this->assertTrue($settings->isEnabledVariant('bz2'));
        $this->assertTrue($settings->isEnabledVariant('calendar'));
        $this->assertTrue($settings->isEnabledVariant('cli'));
        $this->assertTrue($settings->isEnabledVariant('ctype'));
        $this->assertTrue($settings->isEnabledVariant('dom'));
        $this->assertTrue($settings->isEnabledVariant('fileinfo'));
        $this->assertTrue($settings->isEnabledVariant('filter'));
        $this->assertTrue($settings->isEnabledVariant('ipc'));
        $this->assertTrue($settings->isEnabledVariant('json'));
        $this->assertTrue($settings->isEnabledVariant('mbregex'));
        $this->assertTrue($settings->isEnabledVariant('mbstring'));
        $this->assertTrue($settings->isEnabledVariant('mhash'));
        $this->assertTrue($settings->isEnabledVariant('pcntl'));
        $this->assertTrue($settings->isEnabledVariant('pcre'));
        $this->assertTrue($settings->isEnabledVariant('pdo'));
        $this->assertTrue($settings->isEnabledVariant('phar'));
        $this->assertTrue($settings->isEnabledVariant('posix'));
        $this->assertTrue($settings->isEnabledVariant('readline'));
        $this->assertTrue($settings->isEnabledVariant('sockets'));
        $this->assertTrue($settings->isEnabledVariant('tokenizer'));
        $this->assertTrue($settings->isEnabledVariant('xml'));
        $this->assertTrue($settings->isEnabledVariant('curl'));
        $this->assertTrue($settings->isEnabledVariant('zip'));
        $this->assertTrue($settings->isEnabledVariant('openssl'));
    }

    public function testAdditionalEnabledVariants()
    {
        $variants = array(
            'sqlite' => true
        );
        $settings = new DefaultBuildSettings(array('enabled_variants' => $variants));
        $this->assertTrue($settings->getVariant('sqlite'));
        $this->assertTrue($settings->isEnabledVariant('curl'));
    }
}

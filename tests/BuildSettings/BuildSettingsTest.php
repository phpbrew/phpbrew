<?php

namespace PHPBrew\Tests\BuildSettings;

use PHPBrew\BuildSettings\BuildSettings;
use PHPUnit\Framework\TestCase;

class BuildSettingsTest extends TestCase
{
    public function testConstructorWithEnabledVariants()
    {
        $settings = new BuildSettings(array(
            'enabled_variants' => array(
                'sqlite' => true
            )
        ));

        $this->assertTrue($settings->isEnabledVariant('sqlite'));
        $this->assertFalse($settings->isDisabledVariant('sqlite'));
    }

    public function testConstructorWithDisabledVariants()
    {
        $settings = new BuildSettings(array(
            'disabled_variants' => array(
                'sqlite' => true
            )
        ));

        $this->assertTrue($settings->isDisabledVariant('sqlite'));
        $this->assertFalse($settings->isEnabledVariant('sqlite'));
    }

    public function testToArray()
    {
        $enabledVariants = array(
            'sqlite' => true,
            'curl' => 'yes'
        );
        $disabledVariants = array(
            'dom' => true
        );
        $extraOptions = array();
        $settings = new BuildSettings(array(
            'enabled_variants'  => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options'     => $extraOptions
        ));

        $expected = array(
            'enabled_variants'  => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options'     => $extraOptions
        );
        $this->assertEquals($expected, $settings->toArray());
    }

    public function testEnableVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('curl', true);

        $this->assertTrue($settings->isEnabledVariant('curl'));
    }

    public function testEnableVariants()
    {
        $variants = array(
            'sqlite' => true,
            'curl'   => 'yes',
            'dom'    => true
        );
        $settings = new BuildSettings();
        $settings->enableVariants($variants);

        $this->assertEquals($variants, $settings->getVariants());
    }

    public function testDisableVariant()
    {
        $settings = new BuildSettings();
        $settings->disableVariant('curl', true);

        $this->assertTrue($settings->isDisabledVariant('curl'));
    }

    public function testdisbleVariants()
    {
        $variants = array(
            'sqlite' => true,
            'curl'   => 'yes',
            'dom'    => true
        );
        $settings = new BuildSettings();
        $settings->disableVariants($variants);

        $expected = array(
            'sqlite' => true,
            'curl'   => true,
            'dom'    => true
        );
        $this->assertEquals($expected, $settings->getDisabledVariants());
    }

    public function testHasVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->disableVariant('curl', true);

        $this->assertTrue($settings->hasVariant('sqlite'));
        $this->assertFalse($settings->hasVariant('curl'));
    }

    public function testRemoveVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);

        $this->assertTrue($settings->hasVariant('sqlite'));
        $settings->removeVariant('sqlite');
        $this->assertFalse($settings->hasVariant('sqlite'));
    }

    public function testResolveVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('posix', true);
        $settings->disableVariant('sqlite', true);
        $settings->disableVariant('curl', true);
        $settings->disableVariant('dom', true);
        $settings->disableVariant('posix', true);

        $this->assertEquals(array('sqlite', 'posix'), $settings->resolveVariants());
    }

    public function testGetVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', true);
        $settings->disableVariant('dom', true);

        $this->assertEquals(array('sqlite' => true, 'curl' => true), $settings->getVariants());
    }

    public function testGetDisabledVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', true);
        $settings->disableVariant('dom', true);

        $this->assertEquals(array('dom' => true), $settings->getDisabledVariants());
    }

    public function testGetVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', 'yes');
        $settings->disableVariant('dom', true);

        $this->assertNull($settings->getVariant('posix'));
        $this->assertTrue($settings->getVariant('sqlite'));
        $this->assertSame('yes', $settings->getVariant('curl'));
    }

    public function testSetExtraOptions()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', 'yes');
        $settings->disableVariant('dom', true);

        $this->assertNull($settings->getVariant('posix'));
        $this->assertTrue($settings->getVariant('sqlite'));
        $this->assertSame('yes', $settings->getVariant('curl'));
    }
}

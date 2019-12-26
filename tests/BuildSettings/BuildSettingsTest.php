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
                'sqlite' => null
            )
        ));

        $this->assertTrue($settings->isEnabledVariant('sqlite'));
    }

    public function testConstructorWithDisabledVariants()
    {
        $settings = new BuildSettings(array(
            'disabled_variants' => array(
                'sqlite' => true
            )
        ));

        $this->assertFalse($settings->isEnabledVariant('sqlite'));
    }

    public function testToArray()
    {
        $enabledVariants = array(
            'sqlite' => null,
            'curl' => 'yes',
        );
        $disabledVariants = array(
            'dom' => null,
        );
        $extraOptions = array();
        $settings = new BuildSettings(array(
            'enabled_variants'  => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options'     => $extraOptions,
        ));

        $expected = array(
            'enabled_variants'  => $enabledVariants,
            'disabled_variants' => $disabledVariants,
            'extra_options'     => $extraOptions,
        );
        $this->assertEquals($expected, $settings->toArray());
    }

    public function testEnableVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('curl');

        $this->assertTrue($settings->isEnabledVariant('curl'));
    }

    public function testEnableVariants()
    {
        $variants = array(
            'sqlite' => null,
            'curl'   => 'yes',
            'dom'    => null
        );
        $settings = new BuildSettings();
        $settings->enableVariants($variants);

        $this->assertEquals($variants, $settings->getEnabledVariants());
    }

    public function testDisableVariants()
    {
        $variants = array(
            'sqlite' => null,
            'curl'   => 'yes',
            'dom'    => null
        );
        $settings = new BuildSettings();
        $settings->disableVariants($variants);

        $expected = array(
            'sqlite' => null,
            'curl'   => null,
            'dom'    => null
        );
        $this->assertEquals($expected, $settings->getDisabledVariants());
    }

    public function testIsEnabledVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->disableVariant('curl');

        $this->assertTrue($settings->isEnabledVariant('sqlite'));
        $this->assertFalse($settings->isEnabledVariant('curl'));
    }

    public function testRemoveVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');

        $this->assertTrue($settings->isEnabledVariant('sqlite'));
        $settings->removeVariant('sqlite');
        $this->assertFalse($settings->isEnabledVariant('sqlite'));
    }

    public function testResolveVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->disableVariant('sqlite');
        $settings->resolveVariants();

        $this->assertEquals(array(), $settings->getEnabledVariants());
    }

    public function testGetVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->enableVariant('curl');
        $settings->disableVariant('dom');

        $this->assertEquals(array('sqlite' => null, 'curl' => null), $settings->getEnabledVariants());
    }

    public function testGetDisabledVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite');
        $settings->enableVariant('curl');
        $settings->disableVariant('dom');

        $this->assertEquals(array('dom' => null), $settings->getDisabledVariants());
    }
}

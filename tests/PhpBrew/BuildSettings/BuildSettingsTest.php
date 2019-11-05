<?php

namespace PhpBrew\Tests\BuildSettings;

use PhpBrew\BuildSettings\BuildSettings;
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

        ok($settings->isEnabledVariant('sqlite'));
        not_ok($settings->isDisabledVariant('sqlite'));
    }

    public function testConstructorWithDisabledVariants()
    {
        $settings = new BuildSettings(array(
            'disabled_variants' => array(
                'sqlite' => true
            )
        ));

        ok($settings->isDisabledVariant('sqlite'));
        not_ok($settings->isEnabledVariant('sqlite'));
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
        is($expected, $settings->toArray());
    }

    public function testEnableVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('curl', true);

        ok($settings->isEnabledVariant('curl'));
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

        is($variants, $settings->getVariants());
    }

    public function testDisableVariant()
    {
        $settings = new BuildSettings();
        $settings->disableVariant('curl', true);

        ok($settings->isDisabledVariant('curl'));
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
        is($expected, $settings->getDisabledVariants());
    }

    public function testHasVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->disableVariant('curl', true);

        ok($settings->hasVariant('sqlite'));
        not_ok($settings->hasVariant('curl'));
    }

    public function testRemoveVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);

        ok($settings->hasVariant('sqlite'));
        $settings->removeVariant('sqlite');
        not_ok($settings->hasVariant('sqlite'));
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

        is(array('sqlite', 'posix'), $settings->resolveVariants());
    }

    public function testGetVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', true);
        $settings->disableVariant('dom', true);

        is(array('sqlite' => true, 'curl' => true), $settings->getVariants());
    }

    public function testGetDisabledVariants()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', true);
        $settings->disableVariant('dom', true);

        is(array('dom' => true), $settings->getDisabledVariants());
    }

    public function testGetVariant()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', 'yes');
        $settings->disableVariant('dom', true);

        is(null, $settings->getVariant('posix'));
        is(true, $settings->getVariant('sqlite'));
        is('yes', $settings->getVariant('curl'));
    }

    public function testSetExtraOptions()
    {
        $settings = new BuildSettings();
        $settings->enableVariant('sqlite', true);
        $settings->enableVariant('curl', 'yes');
        $settings->disableVariant('dom', true);

        is(null, $settings->getVariant('posix'));
        is(true, $settings->getVariant('sqlite'));
        is('yes', $settings->getVariant('curl'));
    }
}

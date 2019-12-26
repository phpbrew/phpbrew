<?php

namespace PHPBrew\Tests;

use PHPBrew\ConfigureParameters;
use PHPUnit\Framework\TestCase;

final class ConfigureParametersTest extends TestCase
{
    private $configureParameters;

    protected function setUp()
    {
        $this->configureParameters = new ConfigureParameters();
    }

    public function testDefaults()
    {
        $this->assertSame(array(), $this->configureParameters->getOptions());
        $this->assertSame(array(), $this->configureParameters->getPkgConfigPaths());
    }

    public function testWithOptionAndValue()
    {
        $this->assertSame(array(
            '--with-foo' => 'bar',
        ), $this->configureParameters
            ->withOption('--with-foo', 'bar')
            ->getOptions());
    }

    public function testWithOptionAndNoValue()
    {
        $this->assertSame(array(
            '--with-foo' => null,
        ), $this->configureParameters
            ->withOption('--with-foo')
            ->getOptions());
    }

    public function testWithSameOptionAndValue()
    {
        $this->assertSame(array(
            '--with-foo' => 'bar',
        ), $this->configureParameters
            ->withOption('--with-foo', 'bar')
            ->withOption('--with-foo', 'bar')
            ->getOptions());
    }

    public function testWithPkgConfigPath()
    {
        $this->assertSame(array(
            '/usr/lib/pkgconfig',
        ), $this->configureParameters
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->getPkgConfigPaths());
    }

    public function testWithSamePkgConfigPath()
    {
        $this->assertSame(array(
            '/usr/lib/pkgconfig',
        ), $this->configureParameters
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->withPkgConfigPath('/usr/lib/pkgconfig')
            ->getPkgConfigPaths());
    }
}

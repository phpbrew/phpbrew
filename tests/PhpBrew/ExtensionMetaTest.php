<?php

namespace PhpBrew;

/**
 * ExtensionMetaTest
 *
 * @group group
 */
class ExtensionMetaTest extends \PHPUnit_Framework_TestCase
{
    protected $meta;

    protected $path;

    public function setUp()
    {
        $this->path = __DIR__ . '/../fixtures/ext';
    }

    public function testMetaPolyfill()
    {
        $name = 'ext';

        $this->meta = new ExtensionMetaPolyfill($name);
        $this->assertEquals($name, $this->meta->getName());
        $this->assertEquals($name, $this->meta->getRuntimeName());
        $this->assertEquals($name . '.so', $this->meta->getSourceFile());
        $this->assertNull($this->meta->getVersion());
        $this->assertFalse($this->meta->isZend());

        $this->assertPaths();
    }

    public function testMetaXml()
    {
        $this->meta = new ExtensionMetaXml($this->path . '/jsonc.package.xml');
        $this->assertEquals('jsonc', $this->meta->getName());
        $this->assertEquals('json', $this->meta->getRuntimeName());
        $this->assertEquals('json' . '.so', $this->meta->getSourceFile());
        $this->assertEquals('1.3.5', $this->meta->getVersion());
        $this->assertFalse($this->meta->isZend());

        $this->assertPaths();
    }

    public function testMetaXmlZend()
    {
        $this->meta = new ExtensionMetaXml($this->path . '/xdebug.package.xml');
        $this->assertEquals('xdebug', $this->meta->getName());
        $this->assertEquals('xdebug', $this->meta->getRuntimeName());
        $this->assertEquals('xdebug' . '.so', $this->meta->getSourceFile());
        $this->assertEquals('2.2.5', $this->meta->getVersion());
        $this->assertTrue($this->meta->isZend());

        $this->assertPaths();
    }

    public function testMetaM4()
    {
        $this->meta = new ExtensionMetaM4($this->path . '/openssl.config.m4');
        $this->assertEquals('openssl', $this->meta->getName());
        $this->assertEquals('openssl', $this->meta->getRuntimeName());
        $this->assertEquals('openssl' . '.so', $this->meta->getSourceFile());
        $this->assertNull($this->meta->getVersion());
        $this->assertFalse($this->meta->isZend());

        $this->assertPaths();
    }

    public function testMetaM4Zend()
    {
        $this->meta = new ExtensionMetaM4($this->path . '/opcache.config.m4');
        $this->assertEquals('opcache', $this->meta->getName());
        $this->assertEquals('opcache', $this->meta->getRuntimeName());
        $this->assertEquals('opcache' . '.so', $this->meta->getSourceFile());
        $this->assertNull($this->meta->getVersion());
        $this->assertTrue($this->meta->isZend());

        $this->assertPaths();
    }

    protected function assertPaths()
    {
        $this->assertStringEndsWith('/ext/' . $this->meta->getName(), $this->meta->getPath());
        $this->assertStringEndsWith($this->meta->getName() . '.ini', $this->meta->getIniFile());
    }

    /**
     * Test for issue #277
     * 
     * @link github.com/phpbrew/phpbrew/issues/277
     */
    public function testIssue277()
    {
        $this->meta = new ExtensionMetaXml('data://,<?xml version="1.0" encoding="ISO-8859-1" ?><package><name>ext_foo</name></package>');
        $this->assertEquals('foo', $this->meta->getName());
        $this->meta = new ExtensionMetaXml('data://,<?xml version="1.0" encoding="ISO-8859-1" ?><package><name>extfoo</name></package>');
        $this->assertEquals('extfoo', $this->meta->getName());
    }
}

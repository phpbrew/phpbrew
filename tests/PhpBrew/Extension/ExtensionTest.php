<?php
namespace PhpBrew\Extension;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\M4Extension;
use PhpBrew\Extension\PeclExtension;
use PhpBrew\Extension\Extension;

/**
 * ExtensionMetaTest
 *
 * @group group
 */
class ExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $meta;

    protected $path;

    public function setUp()
    {
        $this->path = __DIR__ . '/../fixtures/ext';
    }

    public function testXdebug()
    {
        $ext = ExtensionFactory::lookup('xdebug', array($this->path));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\PeclExtension', $ext);
        $this->assertEquals('xdebug', $ext->getName());
        $this->assertEquals('xdebug', $ext->getExtensionName());
        $this->assertEquals('xdebug.so', $ext->getSharedLibraryName());
        $this->assertTrue($ext->isZend());
    }

    public function testOpcache()
    {
        $ext = ExtensionFactory::lookup('opcache', array($this->path));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\M4Extension', $ext);
        $this->assertEquals('opcache', $ext->getName());
        $this->assertEquals('opcache', $ext->getExtensionName());
        $this->assertEquals('opcache.so', $ext->getSharedLibraryName());
        $this->assertTrue($ext->isZend());
    }

    public function testOpenSSL()
    {
        $ext = ExtensionFactory::lookup('openssl', array($this->path));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\M4Extension', $ext);
        $this->assertEquals('openssl', $ext->getName());
        $this->assertEquals('openssl', $ext->getExtensionName());
        $this->assertEquals('openssl.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function testSoap()
    {
        $ext = ExtensionFactory::lookup('soap', array($this->path));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\PeclExtension', $ext);
        $this->assertEquals('soap', $ext->getName());
        $this->assertEquals('soap', $ext->getExtensionName());
        $this->assertEquals('soap.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }


}

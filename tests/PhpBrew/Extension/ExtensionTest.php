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

    /**
     * We use constant because in data provider method the path member is not 
     * setup yet.
     */
    const EXTENSION_DIR = 'tests/fixtures/ext';

    public function testXdebug()
    {
        $ext = ExtensionFactory::lookup('xdebug', array(self::EXTENSION_DIR));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\PeclExtension', $ext);
        $this->assertEquals('xdebug', $ext->getName());
        $this->assertEquals('xdebug', $ext->getExtensionName());
        $this->assertEquals('xdebug.so', $ext->getSharedLibraryName());
        $this->assertTrue($ext->isZend());
    }

    public function testOpcache()
    {
        $ext = ExtensionFactory::lookup('opcache', array(self::EXTENSION_DIR));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\M4Extension', $ext);
        $this->assertEquals('opcache', $ext->getName());
        $this->assertEquals('opcache', $ext->getExtensionName());
        $this->assertEquals('opcache.so', $ext->getSharedLibraryName());
        $this->assertTrue($ext->isZend());
    }

    public function testOpenSSL()
    {
        $ext = ExtensionFactory::lookup('openssl', array(self::EXTENSION_DIR));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\M4Extension', $ext);
        $this->assertEquals('openssl', $ext->getName());
        $this->assertEquals('openssl', $ext->getExtensionName());
        $this->assertEquals('openssl.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function testSoap()
    {
        $ext = ExtensionFactory::lookup('soap', array(self::EXTENSION_DIR));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\PeclExtension', $ext);
        $this->assertEquals('soap', $ext->getName());
        $this->assertEquals('soap', $ext->getExtensionName());
        $this->assertEquals('soap.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function testXhprof()
    {
        $ext = ExtensionFactory::lookup('xhprof', array(self::EXTENSION_DIR));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertInstanceOf('PhpBrew\Extension\PeclExtension', $ext);
        $this->assertEquals('xhprof', $ext->getName());
        $this->assertEquals('xhprof', $ext->getExtensionName());
        $this->assertEquals('xhprof.so', $ext->getSharedLibraryName());
        $this->assertFalse($ext->isZend());
    }

    public function extensionNameProvider() {
        $extNames = scandir(self::EXTENSION_DIR);
        $data = array();

        foreach( $extNames as $extName) {
            if ($extName == "." || $extName == "..") {
                continue;
            }
            $data[] = array($extName);
        }
        return $data;
    }


    /**
     * @dataProvider extensionNameProvider
     */
    public function testGenericExtensionMetaInformation($extName) {
        $ext = ExtensionFactory::lookup('xhprof', array(self::EXTENSION_DIR));
        $this->assertInstanceOf('PhpBrew\Extension\Extension', $ext);
        $this->assertNotEmpty($ext->getName());
    }
}

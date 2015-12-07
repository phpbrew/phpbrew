<?php
use PhpBrew\Variant;

class VariantTest extends PHPUnit_Framework_TestCase
{
    public function testVariant()
    {
        $variant = new Variant('openssl');
        $variant->desc('OpenSSL SSL/TLS cryptography library')
            ->optionName('--with-openssl')
            ->defaultOption('shared')
            ->depends(array(
                'ubuntu-14.04' => array('libopenssl'),
                'ubuntu-14.10' => array('libopenssl'),
                'macports' => array('openssl'),
            ));
        $this->assertEquals('--with-openssl=shared',$variant->toArgument());

        $variant->disableDefaultOption();
        $this->assertEquals('--with-openssl',$variant->toArgument());
    }
}

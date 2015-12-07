<?php
use PhpBrew\Variant;

class VariantTest extends PHPUnit_Framework_TestCase
{
    public function testVariant()
    {
        $variant = new Variant('openssl');
        $variant->desc('OpenSSL SSL/TLS cryptography library');
        $variant->depends(array(
            'ubuntu-14.04' => ['libopenssl'],
            'ubuntu-14.10' => ['libopenssl'],
            'macports' => ['openssl'],
        ));
    }

}

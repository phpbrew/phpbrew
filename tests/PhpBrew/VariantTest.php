<?php
use PhpBrew\Variant;
use PhpBrew\Build;

class VariantTest extends PHPUnit_Framework_TestCase
{

    public function testVariantBuilder()
    {
        $variant = new Variant('ipc');
        $variant->desc('ipc support')
            ->builder(function(Build $build) {
                return array(
                    '--enable-shmop',
                    '--enable-sysvsem',
                    '--enable-sysvshm',
                    '--enable-sysvmsg',
                );
            });

    }

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
        $options = $variant->toArguments();
        $this->assertEquals('--with-openssl=shared', $options[0]);

        $variant->disableDefaultOption();
        $options = $variant->toArguments();
        $this->assertEquals('--with-openssl', $options[0]);
    }
}

<?php
namespace PhpBrew\Distribution;

/**
 * @small
 */
class DistributionUrlPolicyTest extends \PHPUnit\Framework\TestCase
{
    public $policy;

    public function setUp()
    {
        $this->policy = new DistributionUrlPolicy();
    }

    /**
     * @dataProvider versionDataProvider
     */
    public function testBuildUrl($version, $filename, $distUrl, $museum)
    {
        $this->assertSame(
            $distUrl,
            $this->policy->buildUrl($version, $filename, $museum)
        );
    }

    public function testBuildUrlWhenMirrorSiteIsUsed()
    {
        $mirror = 'http://ja.php.net';
        $this->policy->setMirrorSite($mirror);
        $this->assertSame(
            'http://ja.php.net/distributions/php-5.6.23.tar.bz2',
            $this->policy->buildUrl('5.6.23', 'php-5.6.23.tar.bz2')
        );
    }

    public function versionDataProvider() {
        return array(
            array("5.3.29", "php-5.3.29.tar.bz2", "http://museum.php.net/php5/php-5.3.29.tar.bz2", true),
            array("5.4.7", "php-5.4.7.tar.bz2", "http://museum.php.net/php5/php-5.4.7.tar.bz2", true),
            array("5.4.21", "php-5.4.21.tar.bz2", "http://museum.php.net/php5/php-5.4.21.tar.bz2", true),
            array("5.4.22", "php-5.4.22.tar.bz2", "http://www.php.net/get/php-5.4.22.tar.bz2/from/this/mirror", false),
            array("5.6.23", "php-5.6.23.tar.bz2", "http://www.php.net/get/php-5.6.23.tar.bz2/from/this/mirror", false),
        );
    }
}

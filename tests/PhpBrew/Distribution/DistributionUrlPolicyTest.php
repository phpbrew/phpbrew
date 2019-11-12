<?php

namespace PhpBrew\Tests\Distribution;

use PhpBrew\Distribution\DistributionUrlPolicy;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class DistributionUrlPolicyTest extends TestCase
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


    public function versionDataProvider()
    {
        return array(
            array("5.3.29", "php-5.3.29.tar.bz2", "https://museum.php.net/php5/php-5.3.29.tar.bz2", true),
            array("5.4.7", "php-5.4.7.tar.bz2", "https://museum.php.net/php5/php-5.4.7.tar.bz2", true),
            array("5.4.21", "php-5.4.21.tar.bz2", "https://museum.php.net/php5/php-5.4.21.tar.bz2", true),
            array("5.4.22", "php-5.4.22.tar.bz2", "https://www.php.net/distributions/php-5.4.22.tar.bz2", false),
            array("5.6.23", "php-5.6.23.tar.bz2", "https://www.php.net/distributions/php-5.6.23.tar.bz2", false),
        );
    }
}

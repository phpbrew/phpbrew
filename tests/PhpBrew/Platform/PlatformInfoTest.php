<?php
namespace PhpBrew\Platform;

/**
 * @small
 */
class PlatformInfoTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateMacPlatform()
    {
        $this->assertInstanceOf(
            'PhpBrew\Platform\Mac',
            PlatformInfoForTest::createPlatform('Darwin')
        );
    }

    public function testCreateLinuxPlatform()
    {
        $this->assertInstanceOf(
            'PhpBrew\Platform\Linux',
            PlatformInfoForTest::createPlatform('Linux')
        );
    }

    public function testCreateUnknownPlatform()
    {
        $this->assertInstanceOf(
            'PhpBrew\Platform\UnknownPlatform',
            PlatformInfoForTest::createPlatform('xxxxxx')
        );
    }
}

class PlatformInfoForTest extends PlatformInfo
{
    public static function createPlatform($kernelName)
    {
        return parent::createPlatform($kernelName);
    }
}

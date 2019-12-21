<?php

namespace PHPBrew\Tests;

use PHPBrew\Config;
use PHPUnit\Framework\TestCase;

/**
 * You should use predefined $PHPBREW_HOME and $PHPBREW_ROOT (defined
 * in phpunit.xml), because they are used to create directories in
 * PHPBrew\Config class. When you want to set $PHPBREW_ROOT, $PHPBREW_HOME
 * or $HOME, you should get its value by calling `getenv' function and set
 * the value to the corresponding environment variable.
 * @small
 */
class ConfigTest extends TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testGetPhpbrewHomeWhenHOMEIsNotDefined()
    {
        $env = array(
            'PHPBREW_HOME' => null,
            'PHPBREW_ROOT' => null,
            'HOME'         => null
        );
        $this->withEnv($env, function () {
            Config::getHome();
        });
    }

    public function testGetPhpbrewHomeWhenHOMEIsDefined()
    {
        $env = array(
            'HOME'         => getenv('PHPBREW_ROOT'),
            'PHPBREW_HOME' => null
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew/.phpbrew', Config::getHome());
        });
    }

    public function testGetPhpbrewHomeWhenPhpBrewHomeIsDefined()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew', Config::getHome());
        });
    }

    public function testGetPhpbrewRootWhenPhpBrewRootIsDefined()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew', Config::getRoot());
        });
    }

    public function testGetPhpbrewRootWhenHOMEIsDefined()
    {
        $env = array(
            'HOME'         => getenv('PHPBREW_ROOT'),
            'PHPBREW_ROOT' => null
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew/.phpbrew', Config::getRoot());
        });
    }

    public function testGetVariants()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/variants', Config::getVariantsDir());
        });
    }

    public function testGetBuildDir()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/build', Config::getBuildDir());
        });
    }

    public function testGetDistFileDir()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/distfiles', Config::getDistFileDir());
        });
    }

    public function testGetTempFileDir()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/tmp', Config::getTempFileDir());
        });
    }

    public function testGetCurrentPhpName()
    {
        $env = array('PHPBREW_PHP' => '5.6.3');
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('5.6.3', Config::getCurrentPhpName());
        });
    }

    public function testGetCurrentBuildDir()
    {
        $env = array('PHPBREW_PHP' => '5.6.3');
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew/build/5.6.3', Config::getCurrentBuildDir());
        });
    }

    public function testGetPHPReleaseListPath()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/php-releases.json', Config::getPHPReleaseListPath());
        });
    }

    public function testGetInstallPrefix()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/php', Config::getInstallPrefix());
        });
    }

    public function testGetVersionInstallPrefix()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1', Config::getVersionInstallPrefix('5.5.1'));
        });
    }

    public function testGetVersionEtcPath()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/etc', Config::getVersionEtcPath('5.5.1'));
        });
    }

    public function testGetVersionBinPath()
    {
        $this->withEnv(array(), function ($self) {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/bin', Config::getVersionBinPath('5.5.1'));
        });
    }

    public function testGetCurrentPhpConfigBin()
    {
        $env = array(
            'PHPBREW_PHP'  => '5.5.1'
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/bin/php-config', Config::getCurrentPhpConfigBin());
        });
    }

    public function testGetCurrentPhpizeBin()
    {
        $env = array(
            'PHPBREW_PHP'  => '5.5.1'
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/bin/phpize', Config::getCurrentPhpizeBin());
        });
    }

    public function testGetCurrentPhpConfigScanPath()
    {
        $env = array(
            'PHPBREW_PHP'  => '5.5.1'
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1/var/db', Config::getCurrentPhpConfigScanPath());
        });
    }

    public function testGetCurrentPhpDir()
    {
        $env = array(
            'PHPBREW_PHP'  => '5.5.1'
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew/php/5.5.1', Config::getCurrentPhpDir());
        });
    }

    public function testGetLookupPrefix()
    {
        $env = array(
            'PHPBREW_LOOKUP_PREFIX' => getenv('PHPBREW_ROOT'),
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew', Config::getLookupPrefix());
        });
    }

    public function testGetCurrentPhpBin()
    {
        $env = array(
            'PHPBREW_PATH' => getenv('PHPBREW_ROOT'),
        );
        $this->withEnv($env, function ($self) {
            $self->assertStringEndsWith('.phpbrew', Config::getCurrentPhpBin());
        });
    }

    public function testGetConfigParam()
    {
        $env = array(
            'PHPBREW_ROOT' => 'tests/fixtures',
        );
        $this->withEnv($env, function ($self) {
            $config = Config::getConfig();
            $self->assertSame(array('key1' => 'value1', 'key2' => 'value2'), $config);
            $self->assertEquals('value1', Config::getConfigParam('key1'));
            $self->assertEquals('value2', Config::getConfigParam('key2'));
        });
    }

    /**
     * PHPBREW_HOME and PHPBREW_ROOT are automatically defined if
     * the function which invokes this method doesn't set them explicitly.
     * Set PHPBREW_HOME and PHPBREW_ROOT to null when you want to unset them.
     */
    public function withEnv($newEnv, $callback)
    {
        // reset environment variables
        $oldEnv = $this->resetEnv($newEnv + array(
            'HOME'                  => null,
            'PHPBREW_HOME'          => getenv('PHPBREW_HOME'),
            'PHPBREW_PATH'          => null,
            'PHPBREW_PHP'           => null,
            'PHPBREW_ROOT'          => getenv('PHPBREW_ROOT'),
            'PHPBREW_LOOKUP_PREFIX' => null
        ));

        try {
            $callback($this);
            $this->resetEnv($oldEnv);
        } catch (\Exception $e) {
            $this->resetEnv($oldEnv);
            throw $e;
        }
    }

    public function resetEnv($env)
    {
        $oldEnv = array();
        foreach ($env as $key => $value) {
            $oldEnv[$key] = getenv($key);
            $this->putEnv($key, $value);
        }
        return $oldEnv;
    }

    public function putEnv($key, $value)
    {
        $setting = $key;

        if ($value !== null) {
            $setting .= '=' . $value;
        }

        $this->assertTrue(putenv($setting));
    }
}

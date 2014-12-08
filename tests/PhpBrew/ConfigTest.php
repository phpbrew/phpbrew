<?php

/**
 * @small
 */
class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $versions = PhpBrew\Config::getInstalledPhpVersions();
        // ok( $versions );
        // var_dump( $versions );
    }

    /**
     * @expectedException \Exception
     */
    public function testGetPhpbrewHomeWhenHOMEIsNotDefined()
    {
        $this->withEnv(array(), function() {
            PhpBrew\Config::getPhpbrewHome();
        });
    }

    public function testGetPhpbrewHomeWhenHOMEIsDefined()
    {
        $env = array('HOME' => '/home/phpbrew');
        $this->withEnv($env, function() {
            is('/home/phpbrew/.phpbrew', PhpBrew\Config::getPhpbrewHome());
        });
    }

    public function testGetPhpbrewHomeWhenPHPBREW_HOMEIsDefined()
    {
        $env = array('PHPBREW_HOME' => '.phpbrew');
        $this->withEnv($env, function() {
            is('.phpbrew', PhpBrew\Config::getPhpbrewHome());
        });
    }

    public function testGetPhpbrewRootWhenPHPBREW_ROOTIsDefined()
    {
        $env = array('PHPBREW_ROOT' => '.phpbrew');
        $this->withEnv($env, function() {
            is('.phpbrew', PhpBrew\Config::getPhpbrewRoot());
        });
    }

    public function testGetPhpbrewRootWhenHOMEIsDefined()
    {
        $env = array('HOME' => '/home/phpbrew');
        $this->withEnv($env, function() {
            is('/home/phpbrew/.phpbrew', PhpBrew\Config::getPhpbrewRoot());
        });
    }

    public function testGetVariants()
    {
        $env = array(
            'PHPBREW_HOME' => '/home/phpbrew/home',
            'PHPBREW_ROOT' => '/home/phpbrew/root'
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/home/variants', PhpBrew\Config::getVariantsDir());
        });
    }

    public function testGetBuildDir()
    {
        $env = array(
            'PHPBREW_HOME' => '/home/phpbrew/home',
            'PHPBREW_ROOT' => '/home/phpbrew/root'
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/build', PhpBrew\Config::getBuildDir());
        });
    }

    public function testGetCurrentPhpName()
    {
        $env = array('PHPBREW_PHP' => '5.6.3');
        $this->withEnv($env, function() {
            is('5.6.3', PhpBrew\Config::getCurrentPhpName());
        });
    }

    public function testGetCurrentBuildDir()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
            'PHPBREW_PHP'  => '5.6.3'
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/build/5.6.3', PhpBrew\Config::getCurrentBuildDir());
        });
    }

    public function testGetPHPReleaseListPath()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php-releases.json', PhpBrew\Config::getPHPReleaseListPath());
        });
    }

    public function testGetInstallPrefix()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php', PhpBrew\Config::getInstallPrefix());
        });
    }

    public function testGetVersionInstallPrefix()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php/5.5.1', PhpBrew\Config::getVersionInstallPrefix('5.5.1'));
        });
    }

    public function testGetVersionEtcPath()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php/5.5.1/etc', PhpBrew\Config::getVersionEtcPath('5.5.1'));
        });
    }

    public function testGetVersionBinPath()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php/5.5.1/bin', PhpBrew\Config::getVersionBinPath('5.5.1'));
        });
    }

    public function testGetCurrentPhpConfigBin()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
            'PHPBREW_PHP'  => '5.5.1'
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php/5.5.1/bin/php-config', PhpBrew\Config::getCurrentPhpConfigBin());
        });
    }

    public function testGetCurrentPhpizeBin()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
            'PHPBREW_PHP'  => '5.5.1'
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php/5.5.1/bin/phpize', PhpBrew\Config::getCurrentPhpizeBin());
        });
    }

    public function testGetCurrentPhpDir()
    {
        $env = array(
            'PHPBREW_ROOT' => '/home/phpbrew/root',
            'PHPBREW_PHP'  => '5.5.1'
        );
        $this->withEnv($env, function() {
            is('/home/phpbrew/root/php/5.5.1', PhpBrew\Config::getCurrentPhpDir());
        });
    }

    public function testGetLookupPrefix()
    {
        $env = array(
            'PHPBREW_LOOKUP_PREFIX' => '/tmp',
        );
        $this->withEnv($env, function() {
            is('/tmp', PhpBrew\Config::getLookupPrefix());
        });
    }

    public function testGetCurrentPhpBin()
    {
        $env = array(
            'PHPBREW_PATH' => '/opt/bin/php',
        );
        $this->withEnv($env, function() {
            is('/opt/bin/php', PhpBrew\Config::getCurrentPhpBin());
        });
    }

    public function withEnv($newEnv, $callback)
    {
        // reset environment variables
        $oldEnv = $this->resetEnv($newEnv + array(
            'HOME'                  => null,
            'PHPBREW_HOME'          => null,
            'PHPBREW_PATH'          => null,
            'PHPBREW_PHP'           => null,
            'PHPBREW_ROOT'          => null,
            'PHPBREW_LOOKUP_PREFIX' => null
        ));

        try {
            $callback();
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
        if (is_null($value)) {
            ok(putenv($key));
        } else {
            ok(putenv("$key=$value"));
        }
    }
}

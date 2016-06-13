<?php

namespace PhpBrew;

use Serializable;
use PhpBrew\BuildSettings\BuildSettings;

/**
 * A build object contains version information,
 * variant configuration,
 * paths and an build identifier (BuildId).
 */
class Build implements Serializable, Buildable
{
    const ENV_PRODUCTION = 0;
    const ENV_DEVELOPMENT = 1;

    /**
     * States that describe finished task.
     */
    const STATE_NONE = 0;
    const STATE_DOWNLOAD = 1;
    const STATE_EXTRACT = 2;
    const STATE_CONFIGURE = 3;
    const STATE_BUILD = 4;
    const STATE_INSTALL = 5;

    public $name;

    public $version;

    /**
     * @var string The source directory
     */
    public $sourceDirectory;

    /**
     * @var string the directory that contains bin/php, var/..., includes/
     */
    public $installPrefix;

    /**
     * @var string the directory that contains php.ini file.
     */
    protected $configDirectory;

    public $phpEnvironment = self::ENV_DEVELOPMENT;

    /**
     * @var PhpBrew\BuildSettings
     */
    public $settings;

    /**
     * Build state.
     *
     * @var string
     */
    public $state;

    /**
     * environment related information (should be moved to environment class).
     */
    public $osName;

    public $osRelease;

    /**
     * Construct a Build object,.
     *
     * A build object contains the information of all build options, prefix, paths... etc
     *
     * @param string $version       build version
     * @param string $name          build name
     * @param string $installPrefix install prefix
     */
    public function __construct($version, $name = null, $installPrefix = null)
    {
        $this->version = $version;
        $this->name = $name ? $name : Utils::canonicalizeBuildName($version);
        if ($installPrefix) {
            $this->setInstallPrefix($installPrefix);
        } else {
            // TODO: find the install prefix automatically
        }
        $this->setBuildSettings(new BuildSettings());
        $this->osName = php_uname('s');
        $this->osRelease = php_uname('r');
    }

    public function setOSName($osName)
    {
        $this->osName = $osName;
    }

    public function setOSRelease($osRelease)
    {
        $this->osRelease = $osRelease;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setVersion($version)
    {
        $this->version = preg_replace('#^php-#', '', $version);
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function compareVersion($version)
    {
        return version_compare($this->version, $version);
    }

    public function setConfigDirectory($directory)
    {
        $this->configDirectory = $directory;
    }

    public function getConfigDirectory()
    {
        return $this->configDirectory;
    }

    /**
     * PHP Source directory, this method returns value only when source directory is set.
     */
    public function setSourceDirectory($dir)
    {
        $this->sourceDirectory = $dir;
    }

    public function getSourceDirectory()
    {
        if ($this->sourceDirectory && !file_exists($this->sourceDirectory)) {
            mkdir($this->sourceDirectory, 0755, true);
        }

        return $this->sourceDirectory;
    }

    public function isBuildable()
    {
        return file_exists($this->sourceDirectory.DIRECTORY_SEPARATOR.'Makefile');
    }

    public function getBuildLogPath()
    {
        $dir = $this->getSourceDirectory().DIRECTORY_SEPARATOR.'build.log';
        return $dir;
    }

    public function setInstallPrefix($prefix)
    {
        $this->installPrefix = $prefix;
    }

    public function getBinDirectory()
    {
        return $this->installPrefix.DIRECTORY_SEPARATOR.'bin';
    }

    public function getEtcDirectory()
    {
        $etc = $this->installPrefix.DIRECTORY_SEPARATOR.'etc';
        if (!file_exists($etc)) {
            mkdir($etc, 0755, true);
        }

        return $etc;
    }

    public function getVarDirectory()
    {
        return $this->installPrefix.DIRECTORY_SEPARATOR.'var';
    }

    public function getVarConfigDirectory()
    {
        return $this->installPrefix.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'db';
    }

    public function getInstallPrefix()
    {
        return $this->installPrefix;
    }

    /**
     * Returns {prefix}/var/db path.
     */
    public function getCurrentConfigScanPath()
    {
        return $this->installPrefix.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'db';
    }

    public function getPath($subpath)
    {
        return $this->installPrefix.DIRECTORY_SEPARATOR.$subpath;
    }

    /**
     * Returns a build identifier.
     */
    public function getIdentifier()
    {
        $names = array('php');

        $names[] = $this->version;

        if ($variants = $this->getVariants()) {
            $keys = array_keys($variants);
            sort($keys);

            foreach ($keys as $n) {
                $v = $this->getVariant($n);

                if (is_bool($v)) {
                    $names[] = $n;
                } else {
                    $v = preg_replace('#\W+#', '_', $v);
                    $str = $n.'='.$v;
                    $names[] = $str;
                }
            }
        }

        if ($this->phpEnvironment === self::ENV_PRODUCTION) {
            $names[] = 'prod';
        } elseif ($this->phpEnvironment === self::ENV_DEVELOPMENT) {
            $names[] = 'dev';
        }

        return implode('-', $names);
    }

    public function getSourceExtensionDirectory()
    {
        return $this->sourceDirectory.DIRECTORY_SEPARATOR.'ext';
    }

    public function setBuildSettings(BuildSettings $settings)
    {
        $this->settings = $settings;
        if (!$this->getInstallPrefix()) {
            return;
        }
        // TODO: in future, we only stores build meta information, and that
        // also contains the variant info,
        // but for backward compatibility, we still need a method to handle
        // the variant info file..
        $variantFile = $this->getInstallPrefix().DIRECTORY_SEPARATOR.'phpbrew.variants';
        if (file_exists($variantFile)) {
            $this->settings->loadVariantInfoFile($variantFile);
        }
    }

    public function __set_state($data)
    {
        $build = new self($this->version);
        $build->import($data);

        return $build;
    }

    /**
     * XXX: Make sure Serializable interface works for php 5.3.
     */
    public function serialize()
    {
        return serialize($this->export());
    }

    /**
     * Find a installed build by name,
     * currently a $name is a php version, but in future we may have customized
     * name for users.
     *
     * @param string $name
     *
     * @return Build
     */
    public static function findByName($name)
    {
        $prefix = Config::getVersionInstallPrefix($name);
        if (file_exists($prefix)) {
            // a installation exists
            return new self($name, null, $prefix);
        }

        return;
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->import($data);
    }

    public function import($data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Where we store the last finished state, currently for:.
     *
     *  - FALSE or NULL - nothing done yet.
     *  - "download" - distribution file was downloaded.
     *  - "extract"  - distribution file was extracted to the build directory.
     *  - "configure" - configure was done.
     *  - "make"      - make was done.
     *  - "install"   - installation was done.
     *
     * Not used yet.
     */
    public function getStateFile()
    {
        if ($dir = $this->getInstallPrefix()) {
            return $dir.DIRECTORY_SEPARATOR.'phpbrew_status';
        }
    }

    public function setState($state)
    {
        $this->state = $state;
        if ($path = $this->getStateFile()) {
            file_put_contents($path, $state);
        }
    }

    public function getState()
    {
        if ($this->state) {
            return $this->state;
        }
        if ($path = $this->getStateFile()) {
            if (file_exists($path)) {
                return $this->state = intval(file_get_contents($path)) || self::STATE_NONE;
            }
        }

        return self::STATE_NONE;
    }

    public function export()
    {
        return get_object_vars($this);
    }

    public function writeFile($file)
    {
        file_put_contents($file, $this->serialize());
    }

    public function loadFile($file)
    {
        $serialized = file_get_contents($file);
        $this->unserialize($serialized);
    }

    public function __call($m, $a)
    {
        return call_user_func_array(array($this->settings, $m), $a);
    }
}

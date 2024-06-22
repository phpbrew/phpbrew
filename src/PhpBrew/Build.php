<?php

namespace PhpBrew;

use PhpBrew\BuildSettings\BuildSettings;

/**
 * A build object contains version information,
 * variant configuration,
 * paths and an build identifier (BuildId).
 *
 * @method array getEnabledVariants()
 * @method array getDisabledVariants()
 * @method bool isEnabledVariant(string $variant)
 * @method bool isDisabledVariant(string $variant)
 * @method array getExtraOptions()
 * @method enableVariant(string $variant, string $value = null)
 * @method disableVariant(string $variant)
 * @method removeVariant(string $variant)
 * @method array resolveVariants()
 */
class Build implements Buildable
{
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
     * @var BuildSettings
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

    public $osArch;

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
        if (substr($version, 0, 4) === 'php-') {
            $version = substr($version, 4);
        }

        if ($name === null) {
            $name = 'php-' . $version;
        }

        $this->version = $version;
        $this->name    = $name;

        if ($installPrefix) {
            $this->setInstallPrefix($installPrefix);
        }

        $this->setBuildSettings(new BuildSettings());
        $this->osName = php_uname('s');
        $this->osRelease = php_uname('r');
        $this->osArch = php_uname('m');
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function compareVersion($version)
    {
        return version_compare($this->version, $version);
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
        return file_exists($this->sourceDirectory . DIRECTORY_SEPARATOR . 'Makefile');
    }

    public function getBuildLogPath()
    {
        $dir = $this->getSourceDirectory() . DIRECTORY_SEPARATOR . 'build.log';
        return $dir;
    }

    public function setInstallPrefix($prefix)
    {
        $this->installPrefix = $prefix;
    }

    public function getBinDirectory()
    {
        return $this->installPrefix . DIRECTORY_SEPARATOR . 'bin';
    }

    public function getEtcDirectory()
    {
        $etc = $this->installPrefix . DIRECTORY_SEPARATOR . 'etc';
        if (!file_exists($etc)) {
            mkdir($etc, 0755, true);
        }

        return $etc;
    }

    public function getInstallPrefix()
    {
        return $this->installPrefix;
    }

    public function getPath($subpath)
    {
        return $this->installPrefix . DIRECTORY_SEPARATOR . $subpath;
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
        $variantFile = $this->getInstallPrefix() . DIRECTORY_SEPARATOR . 'phpbrew.variants';
        if (file_exists($variantFile)) {
            $this->settings->loadVariantInfoFile($variantFile);
        }
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
            return $dir . DIRECTORY_SEPARATOR . 'phpbrew_status';
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

    public function __call($m, $a)
    {
        return call_user_func_array(array($this->settings, $m), $a);
    }
}

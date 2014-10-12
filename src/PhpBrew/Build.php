<?php
namespace PhpBrew;
use PhpBrew\Version;
use Exception;
use Serializable;
use PhpBrew\Utils;

/**
 * A build object contains version information,
 * variant configuration,
 * paths and an build identifier (BuildId)
 */
class Build implements Serializable
{
    const ENV_PRODUCTION = 0;
    const ENV_DEVELOPMENT = 1;

    public $name;

    public $version;

    /**
     * The source directory
     */
    public $sourceDirectory;

    public $installPrefix;

    public $phpEnvironment = self::ENV_DEVELOPMENT;

    public $settings;

    /**
     * Construct a Build object,
     *
     * A build object contains the information of all build options, prefix, paths... etc
     *
     * @param string $version build version
     * @param string $alias   build alias
     * @param string $prefix  install prefix
     */
    public function __construct($version, $alias = null, $installPrefix = null)
    {
        // Canonicalize the versionName to php-{version}
        $version = Utils::canonicalizeVersionName($version);

        $this->version = $version;
        $this->name = $alias ? $alias : $version;
        $this->settings = new BuildSettings;
        if ($installPrefix) {
            $this->setInstallPrefix($installPrefix);
            // TODO: in future, we only stores build meta information, and that
            // also contains the variant info,
            // but for backward compatibility, we still need a method to handle
            // the variant info file..
            $variantFile = $installPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants';
            if (file_exists($variantFile)) {
                $this->settings->loadVariantInfoFile($variantFile);
            }
        } else {
            // TODO: find the install prefix automatically


        }
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

    /**
     * PHP Source directory, this method returns value only when source directory is set.
     */
    public function setSourceDirectory($dir)
    {
        $this->sourceDirectory = $dir;
    }

    public function getSourceDirectory()
    {
        if (!file_exists($this->sourceDirectory)) {
            mkdir($this->sourceDirectory, 0755, true);
        }
        return $this->sourceDirectory;
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
        return $this->installPrefix . DIRECTORY_SEPARATOR . 'etc';
    }

    public function getVarDirectory()
    {
        return $this->installPrefix . DIRECTORY_SEPARATOR . 'var';
    }

    public function getVarConfigDirectory()
    {
        return $this->installPrefix . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'db';
    }

    public function getInstallPrefix()
    {
        return $this->installPrefix;
    }

    /**
     * Returns {prefix}/var/db path
     */
    public function getCurrentConfigScanPath()
    {
        return $this->installPrefix . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'db';
    }

    public function getPath($subpath)
    {
        return $this->installPrefix . DIRECTORY_SEPARATOR . $subpath;
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
                    $str = $n . '=' . $v;
                    $names[] = $str;
                }
            }

        }

        if ($this->phpEnvironment === self::ENV_PRODUCTION) {
            $names[] = 'prod';
        } elseif ($this->phpEnvironment === self::ENV_DEVELOPMENT) {
            $names[] = 'dev';
        }

        return join('-', $names);
    }


    public function getSourceExtensionDirectory()
    {
        return $this->sourceDirectory . DIRECTORY_SEPARATOR . 'ext';
    }

    public function __set_state($data)
    {
        $build = new self($this->version);
        $build->import($data);
        return $build;
    }


    /**
     * XXX: Make sure Serializable interface works for php 5.3
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
     * @param  string $name
     * @return Build
     */
    public static function findByName($name)
    {
        $prefix = Config::getVersionInstallPrefix($name);
        if (file_exists($prefix)) {
            // a installation exists
            return new self($name, NULL, $prefix);
        }
        return null;
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

    public function __call($m, $a) {
        return call_user_func_array(array($this->settings,$m), $a);
    }
}

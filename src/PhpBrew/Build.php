<?php
namespace PhpBrew;
use PhpBrew\Version;

use Serializable;

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

    public $variants = array();

    public $disabledVariants = array();

    /**
     * The source directory
     */
    public $sourceDirectory;

    public $installPrefix;

    public $extraOptions = array();

    public $phpEnvironment = self::ENV_DEVELOPMENT;

    /**
     * Construct a Build object,
     *
     * A build object contains the information of all build options, prefix, paths... etc
     *
     * @param string $version build version
     * @param string $alias   build alias
     * @param string $prefix  install prefix
     */
    public function __construct($version, $alias = null, $prefix = null)
    {
        $this->version = $version;
        $this->name = $alias ? $alias : $version;
        if ($prefix) {
            $this->setInstallPrefix($prefix);
            // read the build info from $prefix
            /*
            $metaFile = $prefix . DIRECTORY_SEPARATOR . 'build.meta';
            if ( file_exists($metaFile) ) {
                $meta = unserialize(file_get_contents($metaFile));
                if ($meta['name']) {
                    $this->setName($meta['name']);
                }
                if ($meta['version']) {
                    $this->setVersion($meta->version);
                }
            }
            */

            // TODO: in future, we only stores build meta information, and that
            // also contains the variant info,
            // but for backward compatibility, we still need a method to handle
            // the variant info file..
            $variantFile = $prefix . DIRECTORY_SEPARATOR . 'phpbrew.variants';

            if (file_exists($variantFile)) {
                $this->importVariantFromFile($variantFile);
            }
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

    public function enableVariants(array $settings) 
    {
        foreach($settings as $name => $value) {
            $this->enableVariant($name, $value);
        }
    }

    public function enableVariant($name, $value = null)
    {
        $this->variants[$name] = $value ?: true;
    }

    public function disableVariants(array $settings) 
    {
        foreach($settings as $name => $value) {
            $this->disableVariant($name);
        }
    }

    /**
     * Disable variant.
     *
     * @param string $name The variant name.
     */
    public function disableVariant($name)
    {
        $this->disabledVariants[$name] = true;
    }

    public function resolveVariants()
    {
        foreach ($this->disabledVariants as $n => $true) {
            if ($this->hasVariant($n)) {
                $this->removeVariant($n);
            }
        }
    }

    public function isDisabledVariant($name)
    {
        return isset($this->disabledVariants[$name]);
    }

    public function isEnabledVariant($name)
    {
        return isset($this->variants[$name]);
    }

    public function removeDisabledVariant($name)
    {
        unset($this->disabledVariants[$name]);
    }

    /**
     * Set enabled variants.
     *
     * @param array $variants
     */
    public function setVariants($variants)
    {
        $this->variants = $variants;
    }

    /**
     * Check if we've enabled the variant
     *
     * @param  string $name
     * @return bool
     */
    public function hasVariant($name)
    {
        return isset($this->variants[$name]);
    }

    /**
     * Remove enabled variant.
     */
    public function removeVariant($variantName)
    {
        unset($this->variants[$variantName]);
    }


    /**
     * Get enabled variants
     */
    public function getVariants()
    {
        return $this->variants;
    }



    /**
     * Get all disabled variants
     */
    public function getDisabledVariants()
    {
        return $this->disabledVariants;
    }



    /**
     * Returns variant user value
     *
     * @param string $n variant name
     *
     * @return string variant value
     */
    public function getVariant($n)
    {
        if (isset($this->variants[$n])) {
            return $this->variants[$n];
        }

        return null;
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


    public function setExtraOptions($options)
    {
        $this->extraOptions = $options;
    }

    public function getExtraOptions()
    {
        return $this->extraOptions;
    }


    /**
     * Returns a build identifier.
     */
    public function getIdentifier()
    {
        $names = array('php');

        $names[] = $this->version;

        if ($this->variants) {
            $keys = array_keys($this->variants);
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

    public function importVariantFromFile($variantFile)
    {
        // XXX: not implemented yet
        return;

        if (file_exists($variantFile)) {
            $info = unserialize(file_get_contents($variantFile));
            var_dump($info);
            // echo VariantParser::revealCommandArguments($info);
            // XXX: handle info
        }
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
        $prefix = Config::getVersionBuildPrefix($name);

        if (file_exists($prefix)) {
            // a installation exists
            $build = new self($prefix);

            return $build;
        }
        /*
        if ( file_exists($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants') ) {
            $info = unserialize(file_get_contents( $versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants'));
            echo "\n";
            echo str_repeat(' ',19);
            echo VariantParser::revealCommandArguments($info);
        }
        echo "\n";
        */

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
}

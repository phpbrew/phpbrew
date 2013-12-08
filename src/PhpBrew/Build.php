<?php
namespace PhpBrew;
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

    public $sourceDirectory;

    public $installDirectory;

    public $extraOptions = array();

    public $phpEnvironment = self::ENV_DEVELOPMENT;

    public function __construct($prefix = null)
    {
        if ( $prefix ) {
            $this->setInstallDirectory($prefix);

            // read the build info from $prefix
            /*
            $metaFile = $prefix . DIRECTORY_SEPARATOR . 'build.meta';
            if ( file_exists($metaFile) ) {
                $meta = unserialize(file_get_contents($metaFile));
                if ( $meta['name'] ) {
                    $this->setName($meta['name']);
                }
                if ( $meta['version'] ) {
                    $this->setVersion($meta->version);
                }
            }
            */

            // TODO: in future, we only stores build meta information, and that 
            // also contains the variant info,
            // but for backward compatibility, we still need a method to handle 
            // the variant info file..
            $variantFile =  $prefix . DIRECTORY_SEPARATOR . 'phpbrew.variants';
            if ( file_exists($variantFile) ) {
                $this->importVariantFromFile($variantFile);
            }
        }
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setVersion($version)
    {
        $this->version = preg_replace('#^php-#','',$version);
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function compareVersion($version)
    {
        return version_compare($this->version,$version);
    }

    public function enableVariant($name, $value = null)
    {
        $this->variants[$name] = $value ?: true;
    }


    /**
     * Disable variant.
     */
    public function disableVariant($name)
    {
        $this->disabledVariants[$name] = true;
    }

    public function resolveVariants()
    {
        foreach( $this->disabledVariants as $n => $true ) {
            if( $this->hasVariant($n) ) {
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
     * @param string $name
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
     * @return string variant value
     */
    public function getVariant($n)
    {
        if( isset($this->variants[$n]) )
            return $this->variants[$n];
    }


    public function setSourceDirectory($dir)
    {
        $this->sourceDirectory = $dir;
    }

    public function getSourceDirectory()
    {
        return $this->sourceDirectory;
    }


    /**
     * An alias method of getInstallDirectory
     */
    public function getPrefixPath()
    {
        return $this->getInstallDirectory();
    }


    public function getBinPath()
    {
        return $this->getInstallDirectory() . DIRECTORY_SEPARATOR . 'bin';
    }



    public function setInstallDirectory($dir)
    {
        $this->installDirectory = $dir;
    }

    public function getEtcDirectory() 
    {
        return $this->installDirectory . DIRECTORY_SEPARATOR . 'etc';
    }

    public function getVarDirectory() 
    {
        return $this->installDirectory . DIRECTORY_SEPARATOR . 'var';
    }

    public function getVarConfigDirectory()
    {
        return $this->installDirectory . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'db';
    }

    public function getInstallDirectory()
    {
        return $this->installDirectory;
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

        if($this->variants) {
            $keys = array_keys($this->variants);
            sort($keys);

            foreach( $keys as $n ) {
                $v = $this->getVariant($n);
                if( is_bool($v) ) {
                    $names[] = $n;
                } else {
                    $v = preg_replace( '#\W+#', '_', $v );
                    $str = $n . '=' . $v;
                    $names[] = $str;
                }
            }

        }

        if($this->phpEnvironment === self::ENV_PRODUCTION ) {
            $names[] = 'prod';
        } elseif($this->phpEnvironment === self::ENV_DEVELOPMENT ) {
            $names[] = 'dev';
        }
        return join('-', $names);
    }


    public function getSourceExtensionDirectory()
    {
        return $this->sourceDirectory . DIRECTORY_SEPARATOR . 'ext';
    }

    public function importVariantFromFile($variantFile) {
        if ( file_exists($variantFile) ) {
            $info = unserialize(file_get_contents());
            // echo VariantParser::revealCommandArguments($info);
            // XXX: handle info
        }
    }


    public function __set_state($data)
    {
        $build = new self;
        $build->import($data);
        return $build;
    }


    public serialize( void )
    {
        return serialize($this->export());
    }

    /**
     * Find a installed build by name,
     * currently a $name is a php version, but in future we may have customized 
     * name for users.
     *
     * @param string $name
     * @return Build
     */
    static public function findByName($name) 
    {
        $prefix = Config::getVersionBuildPrefix($name);
        if ( file_exists($prefix) ) {
            // a installation exists
            $build = new self($prefix);
            return $build;
        }
        /*
        if( file_exists($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants') ) {
            $info = unserialize(file_get_contents( $versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants'));
            echo "\n";
            echo str_repeat(' ',19);
            echo VariantParser::revealCommandArguments($info);
        }
        echo "\n";
        */
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->import($data);
    }


    public function import($data)
    {
        foreach( $data as $key => $value ) {
            $this->{$key} = $value;
        }
    }

    public function export($data)
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


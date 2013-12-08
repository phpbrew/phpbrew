<?php
namespace PhpBrew;

/**
 * A build object contains version information, 
 * variant configuration, paths and an build identifier (BuildId)
 */
class Build
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
            // read the build info from $prefix
            $metaFile = $prefix . DIRECTORY_SEPARATOR . 'meta.json';
            if ( file_exists($metaFile) ) {
                $meta = json_decode(file_get_contents($metaFile));
                if ( $meta->name ) {
                    $this->setName($meta->name);
                }
                if ( $meta->version ) {
                    $this->setVersion($meta->version);
                }
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

    public function setInstallDirectory($dir)
    {
        $this->installDirectory = $dir;
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
            $build = new self;
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

}




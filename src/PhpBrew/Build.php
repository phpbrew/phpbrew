<?php
namespace PhpBrew;



/**
 * A build object contains version information, 
 * variant configuration, paths and an build identifier (BuildId)
 */
class Build
{
    const ENV_PRODUCTION;
    const ENV_DEVELOPMENT;

    public $version;

    public $variants = array();

    public $phpEnvironment = self::ENV_DEVELOPMENT;

    public function __construct()
    {

    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function addVariant($name, $value = null)
    {
        $this->variants[$name] = $value ?: true;
    }

    public function setVariants($variants)
    {
        $this->variants = $variants;
    }

    public function removeVariant($variantName)
    {
        unset($this->variants[$variantName]);
    }



    /**
     * Returns a build identifier.
     */
    public function getIdentifier()
    {
        $names = array('php');
        $names[] = $this->version;

        if($this->phpEnvironment === self::ENV_PRODUCTION ) {
            $names[] = 'prod';
        } elseif($this->phpEnvironment === self::ENV_DEVELOPMENT ) {
            $names[] = 'dev';
        }


    }

}




<?php
namespace PhpBrew;

class Variants
{
    public function __construct()
    {
        $this->variants['common'] = array(
       
       
        );

        $this->add( '/php-5.4/', array(
            'mysql' => array( 
                    '--with-mysql',
                    '--with-mysqli'
                ),
            'pdo' => array( '--enable-pdo' ),
            'cli' => array( '--enable-cli' ),
        ) );
    }


    /**
     * add and merge new config with common variants config
     *
     * @param string $k version string or pattern
     * @param array  $config config options array
     */
    public function add($k,$config)
    {
        $this->variants[ $k ] = array_merge(
                $this->variants['common'],
                $config
            );
    }


    /**
     * get available variants for $version
     *
     * @param string $version version string
     */
    public function getAvailableVariants($version)
    {
        if( isset($this->variants[$version]) ) 
            return $this->variants;

        /** try to match regular expressions */
        foreach( $this->variants as $k => $variants ) {
            if( strpos($k,'/') === 0 ) {
                if( preg_match( $k , $version ) ) 
                    return $variants;
            }
        }
    }
}


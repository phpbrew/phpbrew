<?php
namespace PhpBrew;

class Variants
{
    public function __construct()
    {
        $this->variants['common'] = array(
            'common' => array(
                '--with-pear',
                '--with-gd',
                '--with-readline',
                '--enable-sockets',
                '--enable-pcntl',
                '--enable-mbstring',
                '--enable-exif',
                '--enable-zip',
                '--enable-ftp',
                '--enable-cgi',
                '--enable-sysvsem',
                '--enable-sysvshm',
                '--enable-shmop',

                '--with-curl=/usr',

                '--with-jpeg-dir=/usr',
                '--with-png-dir=/usr',
                '--with-zlib',
                '--with-zlib-dir=/usr',
                '--with-kerberos',
                '--with-imap-ssl',
                '--with-openssl',
                '--with-mcrypt=/usr',
                '--with-pdo-sqlite',
                '--enable-soap',
                '--enable-xmlreader',
                '--with-xsl',
                '--with-tidy',
                '--with-xmlrpc',

                // database related
                '--with-mysql=mysqlnd',
                '--with-mysqli=mysqlnd',
                '--with-pdo-mysql=mysqlnd',
            ),
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


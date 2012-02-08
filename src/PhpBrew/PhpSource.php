<?php
namespace PhpBrew;
use DOMDocument;


/**
 * parse available downloads
 */
class PhpSource
{

    static function getStasVersions()
    {
        $baseUrl = 'http://downloads.php.net/stas/';
        $html = file_get_contents($baseUrl);
        $dom = new DOMDocument;
        $dom->loadHtml( $html );

        $items = $dom->getElementsByTagName('a');
        $versions = array();
        foreach( $items as $item ) 
        {
            $href = $item->getAttribute('href');
            if( preg_match('/php-(.*?)\.tar\.bz2$/' , $href , $regs ) ) {
                $version = $regs[1];
                $link = $baseUrl . $href;
                $versions[ 'php-' . $version] = array( 'url' => $link );
            }
        }
        return $versions;
    }

    static function getStableVersions()
    {
        // reference: http://www.php.net/releases/
        return array( 
            'php-5.3.10' => array( 'url' => 'http://www.php.net/distributions/php-5.3.10.tar.bz2' ),
            'php-5.3.9' =>  array( 'url' => 'http://www.php.net/distributions/php-5.3.9.tar.bz2' ),
            'php-5.3.8' =>  array( 'url' => 'http://www.php.net/distributions/php-5.3.8.tar.bz2' ),
            'php-5.3.7' =>  array( 'url' => 'http://www.php.net/distributions/php-5.3.7.tar.bz2' ),
            'php-5.3.2' =>  array( 'url' => 'http://museum.php.net/php5/php-5.3.2.tar.bz2'       ),
        );
    }

    static function getSvnVersions()
    {
        //    http://www.php.net/svn.php # svn
        return array( 
            'php-svn-head' => array( 'svn' => 'https://svn.php.net/repository/php/php-src/trunk' ),
            'php-svn-5.3' => array( 'svn' => 'https://svn.php.net/repository/php/php-src/branches/PHP_5_3' ),
            'php-svn-5.4' => array( 'svn' => 'https://svn.php.net/repository/php/php-src/branches/PHP_5_4' ),
        );
    }

    static function getSnapshotVersions()
    {
        // http://snaps.php.net/php5.3-201202070630.tar.bz2
    }

    static function getVersionInfo($version)
    {
        $versions = self::getStableVersions();
        if( isset($versions[$version]) )
            return $versions[ $version ];

        $versions = self::getSvnVersions();
        if( isset($versions[$version]) )
            return $versions[ $version ];

        $versions = self::getStasVersions();
        if( isset($versions[$version]) )
            return $versions[ $version ];
    }

}


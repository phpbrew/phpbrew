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
                $versions[$version] = array( 'url' => $link );
            }
        }
        return $versions;
    }

    static function getSnapshotVersions()
    {
        // http://snaps.php.net/php5.3-201202070630.tar.bz2
    }


}


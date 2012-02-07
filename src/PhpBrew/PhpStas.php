<?php
namespace PhpBrew;
use DOMDocument;


/**
 * parse available downloads
 */
class PhpStas
{

    static function getVersions()
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

}


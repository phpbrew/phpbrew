<?php
namespace PhpBrew\Command;
use DOMDocument;

class KnownCommand extends \CLIFramework\Command
{
    public function brief() { return 'list known PHP versions'; }

    public function execute()
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
                $versions[$version] = $link;
            }
        }

        // var_dump( $versions ); 
        echo "Available versions:\n";
        foreach( $versions as $version => $link ) {
            echo "\t" . $version . "\n";
        }
    }
}







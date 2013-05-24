<?php

namespace PhpBrew;

use DOMDocument;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * parse available downloads
 */
class PhpSource
{
    static function versionCompare($verion1, $verion2)
    {
        if( $verion1 == $verion2 ) {
            return 0;
        }
        return version_compare($verion1, $verion2, '>') ? -1 : 1;
    }

    static function getReleaseManagers()
    {
        return array(
            'stas' => 'Stanislav Malyshev',
            'dsp' => 'David Soria Parra',
        );
    }

    static function getReleaseManagerVersions($id)
    {
        $baseUrl = "http://downloads.php.net/$id/";

        $client = new Client('http://downloads.php.net');
        $html = $client->get('/'.$id)->send()->getBody(true);

        $crawler = new Crawler();

        $versions = array();
        foreach ($crawler->filter('a') as $item) {
            $href = $item->getAttribute('href');
            if( preg_match('/php-(.*?)\.tar\.bz2$/' , $href , $regs ) ) {
                $version = $regs[1];
                $link = $baseUrl . $href;
                $versions[ 'php-' . $version] = array( 'url' => $link );
            }
        }
        return $versions;
    }

    static function getStableVersions($includeOld = false)
    {
        $client = new Client('http://php.net');
        $crawler = new Crawler();

        // reference:
        // http://www.php.net/downloads.php
        // http://www.php.net/releases/
        $downloadUrls = array(
            'downloads.php',
            'releases'
        );

        foreach ($downloadUrls as $downloadUrl) {
            // @todo better error handling.
            try {
                $html = $client->get($downloadUrl)->send()->getBody(true);
                $crawler->add($html);
            } catch (RequestException $e) {
                echo $e->getMessage();
                continue;
            }

            /* $html = @file_get_contents($downloadUrl);
            if( ! $html ) {
                echo "connection eror: $downloadUrl\n";
                continue;
            } */
        }

        $baseUrl = 'http://www.php.net/distributions/';
        $phpFilePattern = '/php-(.*?)\.tar\.bz2/';
        $versions = array();

        foreach ($crawler->filter('a') as $node) {
            $link = $node->getAttribute('href');

            if (preg_match($phpFilePattern, $link, $regs)) {
                if (!$includeOld && version_compare($regs[1],'5.3.0') < 0 ) {
                    continue;
                }

                $version = 'php-' . $regs[1];
                if (strpos($link, '/') === 0 ) {
                    $link = $baseUrl . $version . '.tar.bz2';
                }

                $versions[$version] = array( 'url' => $link );
            }
        }

        uksort($versions, array('self', 'versionCompare'));

        return $versions;
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

    static function getVersionInfo($version, $includeOld = false)
    {
        $versions = self::getStableVersions($includeOld);
        if( isset($versions[$version]) )
            return $versions[ $version ];

        $versions = self::getSvnVersions();
        if( isset($versions[$version]) )
            return $versions[ $version ];

        $managers = self::getReleaseManagers();
        foreach($managers as $id => $fullName) {
            $versions = self::getReleaseManagerVersions($id);
            if( isset($versions[$version]) )
                return $versions[ $version ];
        }
    }

}


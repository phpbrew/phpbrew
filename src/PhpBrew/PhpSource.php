<?php
namespace PhpBrew;
use DOMDocument;

/**
 * parse available downloads
 */
class PhpSource
{
    public static function versionCompare($version1, $version2)
    {
        if ($version1 == $version2) {
            return 0;
        }

        return version_compare($version1, $version2, '>') ? -1 : 1;
    }

    public static function getReleaseManagers()
    {
        return array(
            'stas' => 'Stanislav Malyshev',
            'dsp' => 'David Soria Parra',
            'tyrael' => 'Ferenc Kovács'
        );
    }

    public static function readFromUrl($url)
    {
        if (isset($_SERVER['http_proxy'])) {
            list($proxyHost, $proxyPort) = explode(":", str_replace('http://', '', $_SERVER['http_proxy']));
            $opts = array(
                'http'=>array(
                    'proxy' => sprintf('tcp://%s:%s', $proxyHost, $proxyPort),
                    'request_fulluri' => true
                )
            );
            $streamContext = stream_context_create($opts);

        } else {
            $streamContext = null;
        }

        return @file_get_contents($url, false, $streamContext);
    }

    public static function getReleaseManagerVersions($id)
    {
        $baseUrl = "http://downloads.php.net/$id/";
        $html = self::readFromUrl($baseUrl);
        $dom = new DOMDocument;
        if(false !== $html) $dom->loadHtml($html);
        $items = $dom->getElementsByTagName('a');
        $versions = array();

        foreach ($items as $item) {
            $href = $item->getAttribute('href');

            if (preg_match('/php-(.*?)\.tar\.bz2$/', $href, $regs)) {
                $version = $regs[1];
                $link = $baseUrl . $href;
                $versions[ 'php-' . $version] = array( 'url' => $link );
            }
        }

        return $versions;
    }

    public static function getStableVersions($includeOld = false)
    {
        // reference: http://www.php.net/downloads.php
        //            http://www.php.net/releases/
        $downloadUrls = array(
            'http://www.php.net/downloads.php',
            'http://www.php.net/releases/'
        );
        $phpFilePattern = '/php-(.*?)\.tar\.bz2/';
        $versions = array();

        foreach ($downloadUrls as $downloadUrl) {
            $html = self::readFromUrl($downloadUrl);
            if (! $html) {
                echo "connection error: $downloadUrl\n";
                continue;
            }

            $baseUrl = 'http://www.php.net/get/{php-version}/from/this/mirror';
            $dom = new DOMDocument;
            @$dom->loadHtml($html);
            $items = $dom->getElementsByTagName('a');

            foreach ($items as $item) {
                $link = $item->getAttribute('href');

                if (preg_match($phpFilePattern, $link, $regs)) {
                    if (!$includeOld && version_compare($regs[1], '5.3.0') < 0) {
                        continue;
                    }

                    $version = 'php-' . $regs[1];
                    if (strpos($link, '/') === 0) {
                        $link = str_replace("{php-version}", $version . '.tar.bz2', $baseUrl);
                    }

                    $versions[$version] = array( 'url' => $link );
                }
            }
        }

        uksort($versions, array('self', 'versionCompare'));

        return $versions;
    }

    public static function getSvnVersions()
    {
        //    http://www.php.net/svn.php # svn
        return array(
            'php-svn-head' => array('svn' => 'https://svn.php.net/repository/php/php-src/trunk'),
            'php-svn-5.3' => array('svn' => 'https://svn.php.net/repository/php/php-src/branches/PHP_5_3'),
            'php-svn-5.4' => array('svn' => 'https://svn.php.net/repository/php/php-src/branches/PHP_5_4'),
        );
    }

    public static function getAllVersions($includeOld = false)
    {
        $unstables = array();
        foreach(static::getReleaseManagers() as $id => $manager)
            $unstables = array_merge($unstables, static::getReleaseManagerVersions($id));

        return array_merge(static::getStableVersions($includeOld), $unstables);
    }

    public static function getSnapshotVersions()
    {
        // http://snaps.php.net/php5.3-201202070630.tar.bz2
    }

    public static function getVersionInfo($version, $includeOld = false)
    {
        $versions = self::getStableVersions($includeOld);

        if (isset($versions[$version])) {
            return $versions[$version];
        }

        $versions = self::getSvnVersions();

        if (isset($versions[$version])) {
            return $versions[$version];
        }

        $managers = self::getReleaseManagers();

        foreach ($managers as $id => $fullName) {
            $versions = self::getReleaseManagerVersions($id);

            if (isset($versions[$version])) {
                return $versions[$version];
            }
        }

        return null;
    }
}

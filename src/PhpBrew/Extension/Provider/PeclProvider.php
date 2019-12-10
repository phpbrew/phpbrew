<?php

namespace PhpBrew\Extension\Provider;

use CLIFramework\Logger;
use DOMDocument;
use Exception;
use GetOptionKit\OptionResult;
use PEARX\Channel as PeclChannel;
use PhpBrew\Downloader\DownloadFactory;

class PeclProvider implements Provider
{
    public $site = 'pecl.php.net';
    public $owner = null;
    public $repository = null;
    public $packageName = null;
    public $defaultVersion = 'stable';

    private $logger;
    private $options;

    public function __construct(Logger $logger, OptionResult $options)
    {
        $this->logger = $logger;
        $this->options = $options;
    }

    public static function getName()
    {
        return 'pecl';
    }

    protected function getPackageXml($packageName, $version)
    {
        $channel = new PeclChannel($this->site);
        $baseUrl = $channel->getRestBaseUrl();
        $url = "$baseUrl/r/" . strtolower($packageName);

        $downloader = DownloadFactory::getInstance($this->logger, $this->options);

        // translate version name into numbers
        if (in_array($version, array('stable', 'latest', 'beta'))) {
            $stabilityTxtUrl = $url . '/' . $version . '.txt';
            if ($ret = $downloader->request($stabilityTxtUrl)) {
                $version = (string) $ret;
            } else {
                throw new Exception("Can not translate stability {$version} into exact version name.");
            }
        }
        $xmlUrl = $url . '/' . $version . '.xml';
        if ($ret = $downloader->request($xmlUrl)) {
            $dom = new DOMDocument('1.0');
            $dom->strictErrorChecking = false;
            $dom->preserveWhiteSpace = false;
            // $dom->resolveExternals = false;
            if (false === $dom->loadXml($ret)) {
                throw new Exception("Error in XMl document: $url");
            }

            return $dom;
        }

        return false;
    }

    public function buildPackageDownloadUrl($version = 'stable')
    {
        if ($this->getPackageName() == null) {
            throw new Exception('Repository invalid.');
        }
        $xml = $this->getPackageXml($this->getPackageName(), $version);
        if (!$xml) {
            throw new Exception('Unable to fetch package xml');
        }
        $g = $xml->getElementsByTagName('g');
        $url = $g->item(0)->nodeValue;
        // just use tgz format file.
        return $url . '.tgz';
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    public function getPackageName()
    {
        return $this->packageName;
    }

    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    public function exists($url, $packageName = null)
    {
        $this->setOwner(null);
        $this->setRepository(null);
        $this->setPackageName($url);

        return true;
    }

    public function isBundled($name)
    {
        return in_array(strtolower($name), array(
            'bcmath', 'bz2', 'calendar', 'com_dotnet', 'ctype', 'curl', 'date',
            'dba', 'dom', 'enchant', 'exif', 'fileinfo', 'filter', 'ftp', 'gd',
            'gettext', 'gmp', 'hash', 'iconv', 'imap', 'interbase', 'intl',
            'json', 'ldap', 'libxml', 'mbstring', 'mcrypt', 'mssql', 'mysqli',
            'mysqlnd', 'oci8', 'odbc', 'opcache', 'openssl', 'pcntl', 'pcre',
            'pdo', 'pdo_dblib', 'pdo_firebird', 'pdo_mysql', 'pdo_oci', 'pdo_odbc',
            'pdo_pgsql', 'pdo_sqlite', 'pgsql', 'phar', 'posix', 'pspell',
            'readline', 'recode', 'reflection', 'session', 'shmop', 'simplexml',
            'skeleton', 'snmp', 'soap', 'sockets', 'spl', 'sqlite3', 'standard',
            'sysvmsg', 'sysvsem', 'sysvshm', 'tidy', 'tokenizer', 'wddx', 'xml',
            'xmlreader', 'xmlrpc', 'xmlwriter', 'xsl', 'zip', 'zlib', 'ext_skel',
            'ext_skel_win32',
        ));
    }

    public function buildKnownReleasesUrl()
    {
        return sprintf('https://pecl.php.net/rest/r/%s/allreleases.xml', $this->getPackageName());
    }

    public function parseKnownReleasesResponse($content)
    {
        // convert xml to array
        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $info2 = json_decode($json, true);

        $versionList = array_map(function ($version) {
            return $version['v'];
        }, $info2['r']);

        return $versionList;
    }

    public function getDefaultVersion()
    {
        return $this->defaultVersion;
    }

    public function setDefaultVersion($version)
    {
        $this->defaultVersion = $version;
    }

    public function shouldLookupRecursive()
    {
        return false;
    }

    public function resolveDownloadFileName($version)
    {
        $url = $this->buildPackageDownloadUrl($version);
        // Check if the url is for php source archive
        if (preg_match('/php-.+\.tar\.(bz2|gz|xz)/', $url, $parts)) {
            return $parts[0];
        }

        // try to get the filename through parse_url
        $path = parse_url($url, PHP_URL_PATH);
        if (false === $path || false === strpos($path, '.')) {
            return;
        }

        return basename($path);
    }

    public function extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $cmds = array(
            "tar -C $currentPhpExtensionDirectory -xzf $targetFilePath",
        );

        return $cmds;
    }

    public function postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath)
    {
        $targetPkgDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $this->getPackageName();
        $info = pathinfo($targetFilePath);
        $packageName = $this->getPackageName();

        $cmds = array(
            "rm -rf $targetPkgDir",
            // Move "memcached-2.2.7" to "memcached"
            "mv $currentPhpExtensionDirectory/{$info['filename']} $currentPhpExtensionDirectory/$packageName",
            // Move "ext/package.xml" to "memcached/package.xml"
            "mv $currentPhpExtensionDirectory/package.xml $currentPhpExtensionDirectory/$packageName/package.xml",
        );

        return $cmds;
    }
}

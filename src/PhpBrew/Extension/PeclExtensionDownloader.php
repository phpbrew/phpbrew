<?php
namespace PhpBrew\Extension;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Config;
use PhpBrew\Downloader;
use PhpBrew\Utils;
use PEARX;
use PEARX\Channel as PeclChannel;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;

class PeclExtensionDownloader
{
    public $peclSite = 'pecl.php.net';

    public $logger;

    public $options;

    public function __construct(Logger $logger, OptionResult $options)
    {
        $this->logger = $logger;
        $this->options = $options;
    }

    public function setPeclSite($site)
    {
        $this->peclSite = $site;
    }

    public function findPeclPackageUrl($packageName, $version = 'stable')
    {
        $channel = new PeclChannel($this->peclSite);
        $xml = $channel->fetchPackageReleaseXml($packageName, $version);
        $g = $xml->getElementsByTagName('g');
        $url = $g->item(0)->nodeValue;
        // just use tgz format file.
        return $url . '.tgz';
    }

    public function download($packageName, $version = 'stable')
    {
        $url = $this->findPeclPackageUrl($packageName, $version);
        $downloader = new Downloader\UrlDownloader($this->logger, $this->options);
        $basename = $downloader->resolveDownloadFileName($url);
        $distDir = Config::getDistFileDir();
        $targetFilePath = $distDir . DIRECTORY_SEPARATOR . $basename;
        $downloader->download($url, $targetFilePath);
        $info = pathinfo($basename);

        $currentPhpExtensionDirectory = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';

        // tar -C ~/.phpbrew/build/php-5.5.8/ext -xvf ~/.phpbrew/distfiles/memcache-2.2.7.tgz
        $extensionDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $packageName;
        if (!file_exists($extensionDir)) {
            mkdir($extensionDir, 0755, true);
        }

        $this->logger->info("===> Extracting to $currentPhpExtensionDirectory...");

        $cmds = array(
            "tar -C $currentPhpExtensionDirectory -xf $targetFilePath",
            "rm -rf $currentPhpExtensionDirectory/$packageName",

            // Move "memcached-2.2.7" to "memcached"
            "mv $currentPhpExtensionDirectory/{$info['filename']} $currentPhpExtensionDirectory/$packageName",
            // Move "ext/package.xml" to "memcached/package.xml"
            "mv $currentPhpExtensionDirectory/package.xml $currentPhpExtensionDirectory/$packageName/package.xml",
        );
        foreach($cmds as $cmd) {
            $this->logger->debug($cmd);
            Utils::system($cmd);
        }
        return $extensionDir;
    }

    public function knownReleases($packageName)
    {
        $url = sprintf("http://pecl.php.net/rest/r/%s/allreleases.xml", $packageName);

        if (extension_loaded('curl')) {
            $curlVersionInfo = curl_version();
            $curlOptions = array(CURLOPT_USERAGENT => 'curl/'. $curlVersionInfo['version']);
            $downloader = new CurlDownloader;
            $downloader->setProgressHandler(new ProgressBar);

            if (! $this->options || ($this->options && ! $this->options->{'no-progress'}) ) {
                $downloader->setProgressHandler(new ProgressBar);
            }

            if ($this->options) {
                if ($proxy = $this->options->{'http-proxy'}) {
                    $downloader->setProxy($proxy);
                }
                if ($proxyAuth = $this->options->{'http-proxy-auth'}) {
                    $downloader->setProxyAuth($proxyAuth);
                }
            }
            $info = $downloader->request($url, array(), $curlOptions);
        } else {
            $info = file_get_contents($url);
        }

        // convert xml to array
        $xml = simplexml_load_string($info);
        $json = json_encode($xml);
        $info2 = json_decode($json, TRUE);

        $versionList = array_map(function($version) {
            return $version['v'];
        }, $info2['r']);

        return $versionList;

    }
}

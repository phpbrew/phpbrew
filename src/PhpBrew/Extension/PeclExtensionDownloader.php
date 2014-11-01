<?php
namespace PhpBrew\Extension;
use PhpBrew\Extension\Extension;
use PhpBrew\Extension\ExtensionInstaller;
use PhpBrew\Config;
use PhpBrew\Downloader;
use PhpBrew\Utils;
use PEARX;
use PEARX\Channel as PeclChannel;
use CLIFramework\Logger;

class PeclExtensionDownloader
{
    public $peclSite = 'pecl.php.net';

    public $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
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
        $downloader = new Downloader\UrlDownloader($this->logger);
        $basename = $downloader->resolveDownloadFileName($url);
        $distDir = Config::getDistFileDir();
        $targetFilePath = $distDir . DIRECTORY_SEPARATOR . $basename;
        $downloader->download($url, $targetFilePath);
        $info = pathinfo($basename);

        $currentPhpExtensionDirectory = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';

        // tar -C ~/.phpbrew/build/php-5.5.8/ext -xvf ~/.phpbrew/distfiles/memcache-2.2.7.tgz
        $extensionDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $packageName;

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
}

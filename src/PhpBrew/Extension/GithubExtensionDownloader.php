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
use GetOptionKit\OptionResult;

class GithubExtensionDownloader
{
    public $githubSite = 'github.com';

    public $logger;

    public $options;

    public function __construct(Logger $logger, OptionResult $options)
    {
        $this->logger = $logger;
        $this->options = $options;
    }

    public function setGithubSite($site)
    {
        $this->githubSite = $site;
    }

    public function getGithubTarballUrl($owner, $repos, $version='stable')
    {
        if (empty($owner) || empty($repos)) {
            throw new Exception("Username or Repository invalid.");
        }
        return sprintf('https://%s/%s/%s/tarball/%s', $this->githubSite, $owner, $repos, $version);
    }

    public function download($owner, $repos, $packageName, $version = 'stable', $isExtSubdir = false)
    {
        $url = $this->getGithubTarballUrl($owner, $repos, $version);
        $downloader = new Downloader\UrlDownloader($this->logger, $this->options);
        $basename = sprintf("%s-%s-%s.tar.gz", $owner, $repos, $version);
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
            "tar -C $currentPhpExtensionDirectory -xzf $targetFilePath",
            "rm -rf $currentPhpExtensionDirectory/$packageName",
            "mv $currentPhpExtensionDirectory/{$owner}-{$repos}-* $currentPhpExtensionDirectory/$packageName"
        );

        foreach($cmds as $cmd) {
            $this->logger->debug($cmd);
            Utils::system($cmd);
        }
        return $extensionDir;
    }
}

<?php
namespace PhpBrew\Extension;

use PhpBrew\Config;
use PhpBrew\Downloader;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Extension\Provider\Provider;
use PhpBrew\Utils;
use PEARX;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;

class ExtensionDownloader
{

    public $logger;

    public $options;

    public function __construct(Logger $logger, OptionResult $options)
    {
        $this->logger = $logger;
        $this->options = $options;
    }


    public function buildGithubTarballUrl($owner, $repos, $version='stable')
    {
        if (empty($owner) || empty($repos)) {
            throw new Exception("Username or Repository invalid.");
        }
        return sprintf('https://%s/%s/%s/archive/%s.tar.gz', $this->githubSite, $owner, $repos, $version);
    }

    public function download(Provider $provider, $version = 'stable')
    {
        $url = $provider->buildPackageDownloadUrl($version);
        $basename = $provider->resolveDownloadFileName($version);
        $distDir = Config::getDistFileDir();
        $targetFilePath = $distDir . DIRECTORY_SEPARATOR . $basename;
        DownloadFactory::getInstance($this->logger, $this->options)->download($url, $targetFilePath);
        $info = pathinfo($basename);

        $currentPhpExtensionDirectory = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';

        // tar -C ~/.phpbrew/build/php-5.5.8/ext -xvf ~/.phpbrew/distfiles/memcache-2.2.7.tgz
        $extensionDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $provider->getPackageName();
        if (!file_exists($extensionDir)) {
            mkdir($extensionDir, 0755, true);
        }

        $this->logger->info("===> Extracting to $currentPhpExtensionDirectory...");

        $cmds = array_merge($provider->extractPackageCommands($currentPhpExtensionDirectory, $targetFilePath),
            $provider->postExtractPackageCommands($currentPhpExtensionDirectory, $targetFilePath));

        foreach ($cmds as $cmd) {
            $this->logger->debug($cmd);
            Utils::system($cmd);
        }
        return $extensionDir;
    }

    public function knownReleases(Provider $provider)
    {
        $url = $provider->buildKnownReleasesUrl();
        $file = DownloadFactory::getInstance($this->logger, $this->options)->download($url);
        $info = file_get_contents($file);
        return $provider->parseKnownReleasesResponse($info);
    }

    public function renameSourceDirectory(Extension $ext)
    {
        $currentPhpExtensionDirectory = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';
        $extName = $ext->getExtensionName();
        $name = $ext->getName();
        $extensionDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $extName;
        $extensionExtractDir = $currentPhpExtensionDirectory . DIRECTORY_SEPARATOR . $name;

        if ($name != $extName) {
            $this->logger->info("===> Rename source directory to $extensionDir...");

            $cmds = array(
                "rm -rf $extensionDir",
                "mv $extensionExtractDir $extensionDir"
            );

            foreach ($cmds as $cmd) {
                $this->logger->debug($cmd);
                Utils::system($cmd);
            }

            // replace source directory to new source directory
            $sourceDir = str_replace($extensionExtractDir, $extensionDir, $ext->getSourceDirectory());
            $ext->setSourceDirectory($sourceDir);
            $ext->setName($extName);
        }
    }
}

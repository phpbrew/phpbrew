<?php
namespace PhpBrew\Extension;
use PhpBrew\Console;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Config;
use PhpBrew\Downloader;
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
        $downloader = new Downloader\UrlDownloader($this->logger, $this->options);
        $basename = $provider->resolveDownloadFileName($version);
        $distDir = Config::getDistFileDir();
        $targetFilePath = $distDir . DIRECTORY_SEPARATOR . $basename;
        $downloader->download($url, $targetFilePath);
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

        foreach($cmds as $cmd) {
            $this->logger->debug($cmd);
            Utils::system($cmd);
        }
        return $extensionDir;
    }

    public function knownReleases(Provider $provider)
    {
        $url = $provider->buildKnownReleasesUrl();

        if (extension_loaded('curl')) {
            $curlVersionInfo = curl_version();
            $curlOptions = array(CURLOPT_USERAGENT => 'curl/'. $curlVersionInfo['version']);
            $downloader = new CurlDownloader;
            $downloader->setProgressHandler(new ProgressBar);

            $console = Console::getInstance();
            if (! $console->options->{'no-progress'}) {
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

        return $provider->parseKnownReleasesResponse($info);

    }

    public function renameSourceDirectory (Extension $ext)
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

            foreach($cmds as $cmd) {
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

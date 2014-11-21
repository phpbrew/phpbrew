<?php
namespace PhpBrew\Extension;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Config;
use PhpBrew\Downloader;
use PhpBrew\Utils;
use PEARX;
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

    public function buildGithubTarballUrl($owner, $repos, $version='stable')
    {
        if (empty($owner) || empty($repos)) {
            throw new Exception("Username or Repository invalid.");
        }
        return sprintf('https://%s/%s/%s/tarball/%s', $this->githubSite, $owner, $repos, $version);
    }

    public function download($owner, $repos, $packageName, $version = 'stable', $isExtSubdir = false)
    {
        $url = $this->buildGithubTarballUrl($owner, $repos, $version);
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

    public function knownReleases($owner, $repo)
    {
        $url = sprintf("https://api.github.com/repos/%s/%s/tags", $owner, $repo);

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

        $info2 = json_decode($info, TRUE);
        $versionList = array_map(function($version) {
            return $version['name'];
        }, $info2);

        return $versionList;

    }

}

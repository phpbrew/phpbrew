<?php
namespace PhpBrew;
use CLIFramework\Logger;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use GetOptionKit\OptionResult;
use PhpBrew\Config;
use Exception;
use PhpBrew\Extension\Provider;
use PhpBrew\Tasks\FetchExtensionListTask;
use RuntimeException;

class ExtensionList
{


    public function __construct()
    { 
    }

    public static function getProviders()
    {
        static $providers;
        if ($providers) {
            return $providers;
        }
        $providers = array(
            new Extension\Provider\GithubProvider,
            new Extension\Provider\BitbucketProvider,
            new Extension\Provider\PeclProvider
        );
        return $providers;
    }

    public static function getProviderByName($providerName)
    {
        $providers = self::getProviders();

        foreach ($providers as $provider) {
            if ($provider::getName() == $providerName) return $provider;
        }
    }

    public function getExtensionListPath()
    {
        return Config::getPhpbrewRoot() . DIRECTORY_SEPARATOR . 'extensions.json';
    }

    public function getRemoteExtensionListUrl($branch)
    {
        if (!extension_loaded('openssl')) {
            throw new Exception('openssl extension not found, to download release json file you need openssl.');
        }
        return "https://raw.githubusercontent.com/phpbrew/phpbrew/$branch/assets/extensions.json";
    }

    public function loadJson($json)
    {
        if (!$json) {
            throw new RuntimeException("Can't load extensions. Empty JSON given.");
        }
        if ($extensions = json_decode($json, true)) {
            return $extensions;
        } else {
            throw new RuntimeException("Can't decode extension json, invalid JSON string: " . substr($json,0, 125) );
        }
    }

    public function loadJsonFile($file) 
    {
        $this->loadJson(file_get_contents($file));
    }

    public function loadLocalExtensionList() {
        $extensionListFile = $this->getExtensionListPath();
        if ($json = file_get_contents($extensionListFile)) {
            return $this->loadJson($json);
        }
        return array();
    }

    public function fetchRemoteExtensionList($branch = 'master', OptionResult $options = NULL) {
        $url = $this->getRemoteExtensionListUrl($branch);
        if (is_null($url)) return array();

        if (extension_loaded('curl')) {
            $curlVersionInfo = curl_version();
            $curlOptions = array(CURLOPT_USERAGENT => 'curl/'. $curlVersionInfo['version']);
            $downloader = new CurlDownloader;
            $downloader->setProgressHandler(new ProgressBar);

            if (! $options || ($options && ! $options->{'no-progress'}) ) {
                $downloader->setProgressHandler(new ProgressBar);
            }

            if ($options) {
                if ($proxy = $options->{'http-proxy'}) {
                    $downloader->setProxy($proxy);
                }
                if ($proxyAuth = $options->{'http-proxy-auth'}) {
                    $downloader->setProxyAuth($proxyAuth);
                }
            }
            $json = $downloader->request($url, array(), $curlOptions);
        } else {
            $json = file_get_contents($url);
        }
        $localFilepath = $this->getExtensionListPath();
        if (false === file_put_contents($localFilepath, $json)) {
            throw new Exception("Can't store release json file");
        }
        return $this->loadJson($json);
    }

    public function foundLocalExtensionList() {
            $extensionListFile = $this->getExtensionListPath();
            return file_exists($extensionListFile);
    }

    public function initLocalExtensionList(Logger $logger, OptionResult $options)
    {
        if (!$this->foundLocalExtensionList() || $options->update) {
            $fetchTask = new FetchExtensionListTask($logger, $options);
            return $fetchTask->fetch($hosting, 'master');
        }
        return array();
    }

    static public function getReadyInstance($branch = 'master', Logger $logger = NULL) {
        static $instance;
        if ($instance) {
            return $instance;
        }
        $instance = new self;

        if ($instance->foundLocalExtensionList()) {
            $instance->loadLocalExtensionList();
        } else {
            $instance->fetchRemoteExtensionList($branch, $logger);
        }

        return $instance;
    }

    public function exists($extensionName)
    {

        $packageName = NULL;
        $extensions = array();
        if ($this->foundLocalExtensionList()) {
            $extensions = $this->loadLocalExtensionList();
        }

        if (isset($extensions[$extensionName])) {
            $providerName = $extensions[$extensionName]['provider'];
            $provider = self::getProviderByName($providerName);

            if($provider->exists($extensions[$extensionName]['url'], $packageName)) return $provider;

        } else {

            // determine which provider support this extension
            $providers = self::getProviders();
            foreach ($providers as $provider) {
                if($provider->exists($extensionName)) return $provider;
            }
        }

        return false;

    }
    
}




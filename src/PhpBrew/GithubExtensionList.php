<?php
namespace PhpBrew;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use PhpBrew\Config;
use Exception;
use PhpBrew\Tasks\FetchGithubExtensionListTask;
use RuntimeException;

class GithubExtensionList
{

    /**
     * $extension['name'] = {}
     */
    public $extensions = array();

    public function __construct($extensions = array())
    { 
        $this->extensions = $extensions;
    }

    public function setExtensions(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function loadJson($json)
    {
        if (!$json) {
            throw new RuntimeException("Can't load extensions. Empty JSON given.");
        }
        if ($extensions = json_decode($json, true)) {
            $this->setExtensions($extensions);
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
        $extensionListFile = Config::getGithubExtensionListPath();
        if ($json = file_get_contents($extensionListFile)) {
            return $this->loadJson($json);
        }
        return false;
    }

    public function getRemoteExtensionListUrl($branch)
    {
        if (!extension_loaded('openssl')) {
            throw new Exception('openssl extension not found, to download release json file you need openssl.');
        }
        return "https://raw.githubusercontent.com/phpbrew/phpbrew/$branch/assets/github-extensions.json";
    }

    public function fetchRemoteExtensionList($branch = 'master', $options = NULL) {
        $curlOptions = array(CURLOPT_USERAGENT => 'curl/'. curl_version()['version']);
        $json = '';
        $url = $this->getRemoteExtensionListUrl($branch);
        if (extension_loaded('curl')) {
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
        $localFilepath = Config::getGithubExtensionListPath();
        if (false === file_put_contents($localFilepath, $json)) {
            throw new Exception("Can't store release json file");
        }
        return $this->loadJson($json);
    }

    public function foundLocalExtensionList() {
        $extensionListFile = Config::getGithubExtensionListPath();
        return file_exists($extensionListFile);
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    static public function getReadyInstance($branch = 'master', $logger = NULL) {
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

    public function checkGithubExtension($extensionName)
    {

        $githubExtensions = array();
        if ($this->foundLocalExtensionList()) {
            $githubExtensions = $this->loadLocalExtensionList();
        }

        if (isset($githubExtensions[$extensionName])) {
            $extensionUrl = $githubExtensions[$extensionName]['url'];
        } else {
            $extensionUrl = $extensionName;
            $extensionName = basename($extensionUrl);
        }

        $matches = array();
        // check url scheme is git@github.com and convert to https
        if (preg_match("#git@github.com:([0-9a-zA-Z-.]*)/([0-9a-zA-Z-.]*).git#", $extensionUrl, $matches)) {
            $extensionUrl = sprintf("https://github.com/%s/%s", $matches[1], $matches[2]);
            $extensionName = $matches[2];
        }

        // parse owner and repository
        if (preg_match("#https://github.com/([0-9a-zA-Z-.]*)/([0-9a-zA-Z-.]*)#", $extensionUrl, $matches)) {
            return array('owner' => $matches[1], 'repository' => $matches[2], 'name' => $extensionName);
        }else {
            return false;
        }

    }

}




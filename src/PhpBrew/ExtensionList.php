<?php
namespace PhpBrew;
use CLIFramework\Logger;
use CurlKit\CurlDownloader;
use CurlKit\Progress\ProgressBar;
use GetOptionKit\OptionResult;
use PhpBrew\Config;
use Exception;
use PhpBrew\Extension\Hosting;
use PhpBrew\Tasks\FetchExtensionListTask;
use RuntimeException;

class ExtensionList
{


    public function __construct()
    { 
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

    public function loadLocalExtensionList(Hosting &$hosting) {
        $extensionListFile = $hosting->getExtensionListPath();
        if (is_null($extensionListFile)) return array();
        if ($json = file_get_contents($extensionListFile)) {
            return $this->loadJson($json);
        }
        return false;
    }

    public function fetchRemoteExtensionList(Hosting &$hosting, $branch = 'master', OptionResult $options = NULL) {
        $url = $hosting->getRemoteExtensionListUrl($branch);
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
        $localFilepath = $hosting->getExtensionListPath();
        if (false === file_put_contents($localFilepath, $json)) {
            throw new Exception("Can't store release json file");
        }
        return $this->loadJson($json);
    }

    public function foundLocalExtensionList(Hosting &$hosting) {
            $extensionListFile = $hosting->getExtensionListPath();
            if (is_null($extensionListFile)) return true;
            return file_exists($extensionListFile);
    }

    public function initLocalExtensionList(Logger $logger, OptionResult $options)
    {
        $hostings = Config::getSupportedHostings();
        foreach ($hostings as $hosting) {
            if (!$this->foundLocalExtensionList($hosting) || $options->update) {
                $fetchTask = new FetchExtensionListTask($logger, $options);
                $fetchTask->fetch($hosting, 'master');
            }
        }
    }

    static public function getReadyInstance($branch = 'master', Logger $logger = NULL) {
        static $instance;
        if ($instance) {
            return $instance;
        }
        $instance = new self;

        $hostings = Config::getSupportedHostings();
        foreach ($hostings as $hosting) {
            if ($instance->foundLocalExtensionList($hosting)) {
                $instance->loadLocalExtensionList($hosting);
            } else {
                $instance->fetchRemoteExtensionList($branch, $logger);
            }
        }
        return $instance;
    }

    public function exists($extensionName)
    {

        $packageName = NULL;
        $hostings = Config::getSupportedHostings();
        foreach ($hostings as $hosting) {
            $extensions = array();
            if ($this->foundLocalExtensionList($hosting)) {
                $extensions = $this->loadLocalExtensionList($hosting);
            }

            if (isset($extensions[$extensionName])) {
                $extensionUrl = $extensions[$extensionName]['url'];
                $packageName = $extensionName;
            } else {
                $extensionUrl = $extensionName;
            }

            $isExists = $hosting->exists($extensionUrl, $packageName);
            if ($isExists) return $hosting;

        }
        return false;

    }
    
}




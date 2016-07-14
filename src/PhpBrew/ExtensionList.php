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
            if ($provider::getName() == $providerName) {
                return $provider;
            }
        }
    }

    public static function getReadyInstance($branch = 'master', Logger $logger = null)
    {
        static $instance;
        if ($instance) {
            return $instance;
        }
        $instance = new self;

        return $instance;
    }

    public function exists($extensionName)
    {


        // determine which provider support this extension
        $providers = self::getProviders();
        foreach ($providers as $provider) {
            if ($provider->exists($extensionName)) {
                return $provider;
            }
        }

        return false;
    }
}

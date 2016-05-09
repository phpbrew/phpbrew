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

    private $logger;
    private $options;

    public function __construct(Logger $logger, OptionResult $options)
    {
        $this->logger = $logger;
        $this->options = $options;
    }

    public function getProviders()
    {
        static $providers;
        if ($providers) {
            return $providers;
        }
        $providers = array(
            new Extension\Provider\GithubProvider,
            new Extension\Provider\BitbucketProvider,
            new Extension\Provider\PeclProvider($this->logger, $this->options)
        );
        return $providers;
    }

    public function getProviderByName($providerName)
    {
        $providers = $this->getProviders();

        foreach ($providers as $provider) {
            if ($provider::getName() == $providerName) {
                return $provider;
            }
        }
    }

    public static function getReadyInstance($branch = 'master', Logger $logger = null, OptionResult $options)
    {
        static $instance;
        if ($instance) {
            return $instance;
        }
        $instance = new self($logger, $options);

        return $instance;
    }

    public function exists($extensionName)
    {


        // determine which provider support this extension
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->exists($extensionName)) {
                return $provider;
            }
        }

        return false;
    }
}

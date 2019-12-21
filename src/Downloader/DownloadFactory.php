<?php

namespace PHPBrew\Downloader;

use CLIFramework\Logger;
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionResult;

class DownloadFactory
{
    const METHOD_PHP_CURL = 'php_curl';
    const METHOD_PHP_STREAM = 'php_stream';
    const METHOD_WGET = 'wget';
    const METHOD_CURL = 'curl';

    private static $availableDownloaders = array(
        self::METHOD_PHP_CURL => 'PHPBrew\Downloader\PhpCurlDownloader',
        self::METHOD_PHP_STREAM => 'PHPBrew\Downloader\PhpStreamDownloader',
        self::METHOD_WGET => 'PHPBrew\Downloader\WgetCommandDownloader',
        self::METHOD_CURL => 'PHPBrew\Downloader\CurlCommandDownloader',
    );

    /**
     * When php built-in extensions don't support openssl, we can use curl or wget instead.
     */
    private static $fallbackDownloaders = array('curl', 'wget');

    /**
     * @param Logger       $logger      is used for creating downloader
     * @param OptionResult $options     options used for create downloader
     * @param array        $preferences Use downloader by preferences.
     * @param bool         $requireSsl
     *
     * @return BaseDownloader|null
     */
    protected static function create(Logger $logger, OptionResult $options, array $preferences, $requireSsl = true)
    {
        foreach ($preferences as $prefKey) {
            if (isset(self::$availableDownloaders[$prefKey])) {
                $downloader = self::$availableDownloaders[$prefKey];
                $down = new $downloader($logger, $options);
                if ($down->hasSupport($requireSsl)) {
                    return $down;
                }
            }
        }

        return;
    }

    /**
     * @param Logger       $logger
     * @param OptionResult $options
     * @param string       $downloader
     *
     * @return BaseDownloader
     */
    public static function getInstance(Logger $logger, OptionResult $options, $downloader = null)
    {
        if (is_string($downloader)) {
            //if we specific a downloader class clearly, then it's the only choice
            if (class_exists($downloader) && is_subclass_of($downloader, 'PHPBrew\Downloader\BaseDownloader')) {
                return new $downloader($logger, $options);
            }
            $downloader = array($downloader);
        }
        if (empty($downloader)) {
            $downloader = array_keys(self::$availableDownloaders);
        }

        //if --downloader presents, we will use it as the first choice,
        //even if the caller specific downloader by alias/array
        if ($options->has('downloader')) {
            $logger->info("Found --downloader option, try to use {$options->downloader} as default downloader.");
            $downloader = array_merge(array($options->downloader), $downloader);
        }

        $instance = self::create($logger, $options, $downloader);
        if ($instance === null) {
            $logger->debug('Downloader not found, falling back to command-based downloader.');
            //if all downloader not available, maybe we should throw exceptions here instead of returning null?
            return self::create($logger, $options, self::$fallbackDownloaders);
        } else {
            return $instance;
        }
    }

    public static function addOptionsForCommand(OptionCollection $opts)
    {
        $opts->add('downloader:', 'Use alternative downloader.');
        $opts->add('continue', 'Continue getting a partially downloaded file.');
        $opts->add('http-proxy:', 'HTTP proxy address')
            ->valueName('Proxy host[:port]');
        $opts->add('http-proxy-auth:', 'HTTP proxy authentication')
            ->valueName('Proxy username:password');
        $opts->add(
            'connect-timeout:',
            'Connection timeout'
        )
            ->valueName('Timeout in seconds');

        return $opts;
    }
}

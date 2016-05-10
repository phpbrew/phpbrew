<?php
/**
 * Downloader Factory
 *
 * @date 2015/12/30
 * @author: xiami, yoanlin
 */
namespace PhpBrew\Downloader;

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
        self::METHOD_PHP_CURL   => 'PhpBrew\Downloader\PhpCurlDownloader',
        self::METHOD_PHP_STREAM => 'PhpBrew\Downloader\PhpStreamDownloader',
        self::METHOD_WGET       => 'PhpBrew\Downloader\WgetCommandDownloader',
        self::METHOD_CURL       => 'PhpBrew\Downloader\CurlCommandDownloader',
    );

    /**
     * When php built-in extensions don't support openssl, we can use curl or wget instead
     */
    private static $fallbackDownloaders = array('curl', 'wget');


    /**
     * @param Logger $logger is used for creating downloader
     * @param OptionResult $options options used for create downloader
     * @param array $preferences Use downloader by preferences.
     * @param boolean $requireSsl
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
        return null;
    }

    /**
     * @param Logger $logger
     * @param OptionResult $options
     * @param string $downloader
     * @return BaseDownloader
     */
    public static function getInstance(Logger $logger, OptionResult $options, $downloader = null)
    {
        if (is_string($downloader)) {
            //if we specific a downloader class clearly, then it's the only choice
            if (class_exists($downloader) && is_subclass_of($downloader, 'PhpBrew\Downloader\BaseDownloader')) {
                return new $downloader($logger, $options);
            }
            $downloader = array($downloader);
        }
        if (empty($downloader)) {
            $downloader = array_keys(self::$availableDownloaders);
        }

        //if --downloader presents, we will use it as the first choice, even if the caller specific downloader by alias/array
        if ($options->has('downloader')) {
            $logger->info("Found --downloader option, try to use {$options->downloader} as default downloader.");
            $downloader = array_merge(array($options->downloader), $downloader);
        }

        $instance = self::create($logger, $options, $downloader);
        if ($instance === null) {
            $logger->debug("Downloader not found, falling back to command-based downloader.");
            //if all downloader not available, maybe we should throw exceptions here instead of returning null?
            return self::create($logger, $options, self::$fallbackDownloaders);
        } else {
            return $instance;
        }
    }

    public static function addOptionsForCommand(OptionCollection $opts)
    {
        $opts->add('downloader:', 'Specify downloader instead of the default downloader.');
        $opts->add('continue', 'Continue getting a partially-downloaded file.');
        $opts->add('http-proxy:', 'The HTTP Proxy to download PHP distributions. e.g. --http-proxy=22.33.44.55:8080')
            ->valueName('proxy host');
        $opts->add('http-proxy-auth:', 'The HTTP Proxy Auth to download PHP distributions. user:pass')
            ->valueName('user:pass');
        $opts->add('connect-timeout:', 'Overrides the CONNECT_TIMEOUT env variable and aborts if download takes longer than specified.')
            ->valueName('seconds');
        return $opts;
    }
}

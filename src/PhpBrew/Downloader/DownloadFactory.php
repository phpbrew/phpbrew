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
    private static $availableDownloaders = array(
        'php_curl'   => 'PhpBrew\Downloader\PhpCurlDownloader',
        'php_stream' => 'PhpBrew\Downloader\PhpStreamDownloader',
        'wget'       => 'PhpBrew\Downloader\WgetCommandDownloader',
        'curl'       => 'PhpBrew\Downloader\CurlCommandDownloader',
    );

    /**
     * When php built-in extensions don't support openssl, we can use curl or wget instead
     */
    private static $fallbackDownloaders = array('curl', 'wget');


    /**
     * @param Logger $logger is used for creating downloader
     * @param OptionResult $options options used for create downloader
     * @param array $preferences Use downloader by preferences.
     */
    public static function create(Logger $logger, OptionResult $options, array $preferences, $requireSsl = true)
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
        $logger->debug("Downloader not found, falling back to command-based downloader.");
        return self::create($logger, $options, self::$fallbackDownloaders);
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
            if (class_exists($downloader) && is_subclass_of($downloader, 'PhpBrew\Downloader\BaseDownloader')) {
                return new $downloader($logger, $options);
            }
            return self::create($logger, $options, array($downloader));
        } else if (is_array($downloader)) {
            return self::create($logger, $options, $downloader);
        }
        if (empty($downloader) && $options->has('downloader')) {
            $logger->info("Found --downloader option, try to use {$options->downloader} as default downloader.");
            return self::create($logger, $options, [$options->downloader]);
        }
        return self::create($logger, $options, array_keys(self::$availableDownloaders));
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

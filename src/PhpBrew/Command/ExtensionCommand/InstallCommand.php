<?php
namespace PhpBrew\Command\ExtensionCommand;

use Exception;
use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\Extension\ExtensionDownloader;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\PeclExtensionInstaller;
use PhpBrew\ExtensionList;
use PhpBrew\Utils;

class InstallCommand extends BaseCommand
{
    public function usage()
    {
        return 'phpbrew [-dv, -r] ext install [extension name] [-- [options....]]';
    }

    public function brief()
    {
        return 'Install PHP extension';
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('pecl', 'Try to download from pecl even when ext source is bundled with php-src.');
        $opts->add('redownload', 'Force to redownload extension source even if it is already available.');

        DownloadFactory::addOptionsForCommand($opts);
    }

    public function arguments($args)
    {
        $args->add('extensions')
            ->suggestions(function () {
                $extdir = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';
                return array_filter(
                    scandir($extdir),
                    function ($d) use ($extdir) {
                        return $d != '.' && $d != '..' && is_dir($extdir . DIRECTORY_SEPARATOR . $d);
                    }
                );
            });
    }

    protected function getExtConfig($args)
    {
        $version = 'stable';
        $options = array();

        if (count($args) > 0) {
            $pos = array_search('--', $args);
            if ($pos !== false) {
                $options = array_slice($args, $pos + 1);
            }

            if ($pos === false || $pos == 1) {
                $version = $args[0];
            }
        }
        return (object) array(
            'version' => $version,
            'options' => $options,
        );
    }

    public function prepare()
    {
        parent::prepare();

        $buildDir = Config::getCurrentBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . 'ext';
        if (! is_dir($extDir)) {
            $this->logger->error("Error: The ext directory '$extDir' does not exist.");
            $this->logger->error("It looks like you don't have the PHP source in $buildDir or you didn't extract the tarball.");
            $this->logger->error("Suggestion: Please install at least one PHP with your prefered version and switch to it.");
            return false;
        }
        return true;
    }


    public function execute($extName, $version = 'stable')
    {
        if (version_compare(PHP_VERSION, "7.0.0") > 0) {
            $this->logger->warn(
"Warning: Some extension won't be able to be built with php7. If the extension
supports php7 in another branch or new major version, you will need to specify
the branch name or version name explicitly.

For example, to install memcached extension for php7, use:

    phpbrew ext install github:php-memcached-dev/php-memcached php7 -- --disable-memcached-sasl
"
            );
        }

        if (strtolower($extName) === "apc" && version_compare(PHP_VERSION, "5.6.0") > 0) {
            $this->logger->warn("apc is not compatible with php 5.6+ versions, install apcu instead.");
        }

        // Detect protocol
        if ((preg_match('#^git://#', $extName) || preg_match('#\.git$#', $extName)) && !preg_match("#github|bitbucket#", $extName)) {
            $pathinfo = pathinfo($extName);
            $repoUrl = $extName;
            $extName = $pathinfo['filename'];
            $extDir = Config::getBuildDir() . DIRECTORY_SEPARATOR . Config::getCurrentPhpName() . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . $extName;
            if (!file_exists($extDir)) {
                passthru("git clone $repoUrl $extDir", $ret);
                if ($ret != 0) {
                    return $this->logger->error('Clone failed.');
                }
            }
        }

        // Expand extensionset from config
        $extensions = array();
        if (Utils::startsWith($extName, '+')) {
            $config = Config::getConfigParam('extensions');
            $extName = ltrim($extName, '+');
            if (isset($config[$extName])) {
                foreach ($config[$extName] as $extensionName => $extOptions) {
                    $args = explode(' ', $extOptions);
                    $extensions[$extensionName] = $this->getExtConfig($args);
                }
            } else {
                $this->logger->info('Extension set name not found. Have you configured it at the config.yaml file?');
            }
        } else {
            $args = array_slice(func_get_args(), 1);
            $extensions[$extName] = $this->getExtConfig($args);
        }

        $extensionList = new ExtensionList;

        $manager = new ExtensionManager($this->logger);
        foreach ($extensions as $extensionName => $extConfig) {
            $provider = $extensionList->exists($extensionName);

            if (! $provider) {
                throw new Exception("Could not find provider for $extensionName.");
            }

            $extensionName = $provider->getPackageName();
            $ext = ExtensionFactory::lookupRecursive($extensionName);

            $always_redownload =
                $this->options->{'pecl'} || $this->options->{'redownload'} || (! $provider->isBundled($extensionName));

            // Extension not found, use pecl to download it.
            if (! $ext || $always_redownload) {

                // not every project has stable branch, using master as default version
                $args = array_slice(func_get_args(), 1);
                if (!isset($args[0]) || $args[0] != $extConfig->version) {
                    $extConfig->version = $provider->getDefaultVersion();
                }

                $extensionDownloader = new ExtensionDownloader($this->logger, $this->options);

                $extensionDownloader->download($provider, $extConfig->version);

                // Reload the extension
                if ($provider->shouldLookupRecursive()) {
                    $ext = ExtensionFactory::lookupRecursive($extensionName);
                } else {
                    $ext = ExtensionFactory::lookup($extensionName);
                }

                if ($ext) {
                    $extensionDownloader->renameSourceDirectory($ext);
                }
            }
            if (!$ext) {
                throw new Exception("$extensionName not found.");
            }
            $manager->installExtension($ext, $extConfig->options);
        }
    }
}

<?php
namespace PhpBrew\Command\ExtensionCommand;

use Exception;
use PhpBrew\Config;
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

    public function execute($extName, $version = 'stable')
    {
        if ((preg_match('#^git://#',$extName) || preg_match('#\.git$#', $extName)) && !preg_match("#github.com#", $extName) ) {
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

            if ($provider) $extensionName = $provider->getPackageName();

            $ext = ExtensionFactory::lookupRecursive($extensionName);

            // Extension not found, use pecl to download it.
            if (!$ext) {

                if ($provider) {

                    // not every project has stable branch, using master as default version
                    $args = array_slice(func_get_args(), 1);
                    if (!isset($args[0]) || $args[0] != $extConfig->version) $extConfig->version = $provider->getDefaultVersion();

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

            }
            if (!$ext) {
                throw new Exception("$extensionName not found.");
            }
            $manager->installExtension($ext, $extConfig->options, $this->options->{'pecl'});
        }
    }
}

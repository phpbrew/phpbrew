<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Config;
use PhpBrew\Extension;
use PhpBrew\Utils;
use Symfony\Component\Yaml\Yaml;

class InstallCommand extends \CLIFramework\Command
{
    public function usage()
    {
        return 'phpbrew [-dv] ext install [extension name] [-- [options....]]';
    }

    public function brief()
    {
        return 'Install PHP extension';
    }

    public function options($opts)
    {
        $opts->add('pv|php-version:','The php version for which we install the module.');
    }

    protected function _getExtData($args)
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

        $extData = new \stdClass();
        $extData->version = $version;
        $extData->options = $options;

        return $extData;
    }

    public function execute($extName, $version = 'stable')
    {
        $logger = $this->getLogger();
        $extensions = array();

        if (Utils::startsWith($extName, '+')) {
            $config = Config::getConfigParam('extensions');
            $extName = ltrim($extName, '+');

            if (isset($config[$extName])) {
                foreach ($config[$extName] as $extensionName => $extOptions) {
                    $args = explode(' ', $extOptions);
                    $extensions[$extensionName] = $this->_getExtData($args);
                }
            } else {
                $logger->info('Extension set name not found. Have you configured it at the config.yaml file?');
            }
        } else {
            $args = array_slice(func_get_args(), 1);
            $extensions[$extName] = $this->_getExtData($args);
        }

        if ($this->options->{'php-version'} !== null) {
            $phpVersion = Utils::findLatestPhpVersion($this->options->{'php-version'});
            Config::setPhpVersion($phpVersion);
        }

        foreach ($extensions as $extensionName => $extData) {
            $extension = new Extension($extensionName, $logger);
            $extension->install($extData->version, $extData->options);
        }

        Config::useSystemPhpVersion();
    }
}

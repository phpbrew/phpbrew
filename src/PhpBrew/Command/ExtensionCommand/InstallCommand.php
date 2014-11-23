<?php
namespace PhpBrew\Command\ExtensionCommand;

use Exception;
use PhpBrew\Config;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\GithubExtensionDownloader;
use PhpBrew\Extension\PeclExtensionInstaller;
use PhpBrew\Extension\PeclExtensionDownloader;
use PhpBrew\GithubExtensionList;
use PhpBrew\Tasks\FetchGithubExtensionListTask;
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

        // initial local list
        $extensionList = new GithubExtensionList;

        if (!$extensionList->foundLocalExtensionList() || $this->options->update) {
            $fetchTask = new FetchGithubExtensionListTask($this->logger, $this->options);
            $fetchTask->fetch('master');
        }

        $manager = new ExtensionManager($this->logger);
        foreach ($extensions as $extensionName => $extConfig) {

            $githubExtension = $extensionList->exists($extensionName);

            if ($githubExtension) $extensionName = $githubExtension['name'];

            $ext = ExtensionFactory::lookupRecursive($extensionName);

            // Extension not found, use pecl to download it.
            if (!$ext) {

                if ($githubExtension) {

                    // not every github project has stable branch, using master as default version
                    $args = array_slice(func_get_args(), 1);
                    if (!isset($args[0]) || $args[0] != $extConfig->version) $extConfig->version = 'master';
                    $githubDownloader = new GithubExtensionDownloader($this->logger, $this->options);
                    $githubDownloader->download($githubExtension['owner'], $githubExtension['repository'], $extensionName, $extConfig->version);

                    // Reload the extension
                    $ext = ExtensionFactory::lookupRecursive($extensionName);
                } else {
                    $peclDownloader = new PeclExtensionDownloader($this->logger, $this->options);
                    $peclDownloader->download($extensionName, $extConfig->version);

                    // Reload the extension
                    $ext = ExtensionFactory::lookup($extensionName);
                }

            }
            if (!$ext) {
                throw new Exception("$extensionName not found.");
            }
            $manager->installExtension($ext, $extConfig->options, $this->options->{'pecl'});
        }
    }
}

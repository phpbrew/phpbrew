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
use PhpBrew\Utils;

class InstallCommand extends BaseCommand
{

    public $extensionsHosting = array();

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
        $opts->add('github', 'Try to download from github repository.');
        $opts->add('user', 'github user.');
        $opts->add('repos', 'github repos.');
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

            /*
             * Check if extName is github project
             */
            $extensionList = new GithubExtensionList;

            // initial local list
            if (!$extensionList->foundLocalExtensionList() || $this->options->update) {
                $fetchTask = new FetchGithubExtensionListTask($this->logger, $this->options);
                $fetchTask->fetch('master');
            }
            $githubExtension = $extensionList->checkGithubExtension($extName);
            if ($githubExtension) {
                $this->extensionsHosting[$githubExtension['name']] = array(
                    'site' => 'github',
                    'owner' => $githubExtension['owner'],
                    'repository' => $githubExtension['repository']
                );
                $extName = $githubExtension['name'];
            } else {
                $this->extensionsHosting[$extName] = array(
                    'site' => 'pecl',
                    'repository' => $extName
                );
            }

            $extensions[$extName] = $this->getExtConfig($args);
        }

        $manager = new ExtensionManager($this->logger);
        foreach ($extensions as $extensionName => $extConfig) {
            $ext = ExtensionFactory::lookupRecursive($extensionName);

            // Extension not found, use pecl to download it.
            if (!$ext) {

                if ($this->extensionsHosting[$extensionName]['site'] == 'github') {
                    $githubDownloader = new GithubExtensionDownloader($this->logger, $this->options);
                    $githubDownloader->download($this->extensionsHosting[$extensionName]['owner'], $this->extensionsHosting[$extensionName]['repository'], $extensionName, $extConfig->version);
                }else {
                    $peclDownloader = new PeclExtensionDownloader($this->logger, $this->options);
                    $peclDownloader->download($extensionName, $extConfig->version);
                }

                // Reload the extension
                $ext = ExtensionFactory::lookupRecursive($extensionName);
            }
            if (!$ext) {
                throw new Exception("$extensionName not found.");
            }
            $manager->installExtension($ext, $extConfig->options, $this->options->{'pecl'});
        }
    }
}

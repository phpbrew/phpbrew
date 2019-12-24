<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use Phar;
use PhpBrew\Config;

class InitCommand extends Command
{
    public function brief()
    {
        return 'Initialize phpbrew config file.';
    }

    public function options($opts)
    {
        $opts->add(
            'c|config:',
            'The YAML config file which should be copied into phpbrew home.' .
            'The config file is used for creating custom virtual variants. ' .
            'For more details, please see https://github.com/phpbrew/phpbrew/wiki/Setting-up-Configuration'
        )->isa('file');

        $opts->add(
            'root:',
            'Override the default PHPBREW_ROOT path setting.' .
            'This option is usually used to load system-wide build pool. ' .
            'e.g. phpbrew init --root=/opt/phpbrew '
        )->isa('dir');
    }

    public function execute()
    {
        // $currentVersion;
        $root = $this->options->root ?: Config::getRoot();
        $home = Config::getHome();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getInstallPrefix();
        // $versionBuildPrefix = Config::getVersionInstallPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        $this->logger->info("Using root: $root");
        if (!file_exists($root)) {
            mkdir($root, 0755, true);
        }

        $paths = array();
        $paths[] = $home;
        $paths[] = $root;
        $paths[] = $buildDir;
        $paths[] = $buildPrefix;
        foreach ($paths as $p) {
            $this->logger->debug("Checking directory $p");
            if (!file_exists($p)) {
                $this->logger->debug("Creating directory $p");
                mkdir($p, 0755, true);
            } else {
                $this->logger->debug("Directory $p is already created.");
            }
        }

        $this->logger->debug('Creating .metadata_never_index to prevent SpotLight indexing');
        $indexFiles = array(
            $root . DIRECTORY_SEPARATOR . '.metadata_never_index',
            $home . DIRECTORY_SEPARATOR . '.metadata_never_index',
        );
        foreach ($indexFiles as $indexFile) {
            if (!file_exists($indexFile)) {
                touch($indexFile); // prevent spotlight index here
            }
        }

        if ($configFile = $this->options->{'config'}) {
            if (!file_exists($configFile)) {
                $this->logger->error("config file '$configFile' does not exist.");

                return;
            }
            $this->logger->debug("Using yaml config from '$configFile'");
            copy($configFile, $root . DIRECTORY_SEPARATOR . 'config.yaml');
        }

        $this->logger->writeln($this->formatter->format('Initialization successfully finished!', 'strong_green'));
        $this->logger->writeln(
            $this->formatter->format(
                '<=====================================================>',
                'strong_white'
            )
        );

        // write bashrc script to phpbrew home
        file_put_contents($home . '/bashrc', $this->getBashScriptPath());
        // write phpbrew.fish script to phpbrew home
        file_put_contents($home . '/phpbrew.fish', $this->getFishScriptPath());

        if (strpos(getenv('SHELL'), 'fish') !== false) {
            $initConfig = <<<EOS
Paste the following line(s) to the end of your ~/.config/fish/config.fish and start a
new shell, phpbrew should be up and fully functional from there:

    source $home/phpbrew.fish
EOS;
        } else {
            $initConfig = <<<EOS
Paste the following line(s) to the end of your ~/.bashrc and start a
new shell, phpbrew should be up and fully functional from there:

    source $home/bashrc

To enable PHP version info in your shell prompt, please set PHPBREW_SET_PROMPT=1
in your `~/.bashrc` before you source `~/.phpbrew/bashrc`

    export PHPBREW_SET_PROMPT=1

To enable .phpbrewrc file searching, please export the following variable:

    export PHPBREW_RC_ENABLE=1

EOS;
        }

        echo <<<EOS
Phpbrew environment is initialized, required directories are created under

    $home

$initConfig

For further instructions, simply run `phpbrew` to see the help message.

Enjoy phpbrew at \$HOME!!


EOS;

        $this->logger->writeln(
            $this->formatter->format(
                '<=====================================================>',
                'strong_white'
            )
        );
    }

    protected function getCurrentShellDirectory()
    {
        $path = Phar::running();
        if ($path) {
            $path = $path . DIRECTORY_SEPARATOR . 'shell';
        } else {
            $path = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'shell';
        }

        return $path;
    }

    protected function getBashScriptPath()
    {
        $path = $this->getCurrentShellDirectory();

        return file_get_contents($path . DIRECTORY_SEPARATOR . 'bashrc');
    }

    protected function getFishScriptPath()
    {
        $path = $this->getCurrentShellDirectory();

        return file_get_contents($path . DIRECTORY_SEPARATOR . 'phpbrew.fish');
    }
}

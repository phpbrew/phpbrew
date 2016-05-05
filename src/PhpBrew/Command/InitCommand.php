<?php
namespace PhpBrew\Command;

use Phar;
use PhpBrew\Config;

class InitCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Initialize phpbrew config file.';
    }

    public function options($opts)
    {
        $opts->add('c|config:', 'The config file which should be used.');
    }

    public function execute()
    {
        // $currentVersion;
        $root = Config::getRoot();
        $home = Config::getHome();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getInstallPrefix();
        // $versionBuildPrefix = Config::getVersionInstallPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        if (!file_exists($root)) {
            mkdir($root, 0755, true);
        }

        $paths = array();
        $paths[] = $home;
        $paths[] = $root;
        $paths[] = $root . DIRECTORY_SEPARATOR . 'register';
        $paths[] = $buildDir;
        $paths[] = $buildPrefix;
        foreach ($paths as $p) {
            $this->logger->info("Checking directory $p");
            if (!file_exists($p)) {
                $this->logger->info("Creating directory $p");
                mkdir($p, 0755, true);
            } else {
                $this->logger->info("Directory $p is already created.");
            }
        }

        $this->logger->info('Creating .metadata_never_index to prevent SpotLight indexing');
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
                return $this->logger->error("config file '$configFile' does not exist.");
            }
            $this->logger->debug("Using yaml config from '$configFile'");
            copy($configFile, $root . DIRECTORY_SEPARATOR . 'config.yaml');
        }

        $this->logger->writeln($this->formatter->format("Initialization successfully finished!", 'strong_green'));
        $this->logger->writeln($this->formatter->format("<=====================================================>", 'strong_white'));

        // write bashrc script to phpbrew home
        file_put_contents($home . '/bashrc', $this->getBashScriptPath());
        // write phpbrew.fish script to phpbrew home
        file_put_contents($home . '/phpbrew.fish', $this->getFishScriptPath());

        if (strpos(getenv("SHELL"), "fish") !== false) {
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
        $this->logger->writeln($this->formatter->format("<=====================================================>", 'strong_white'));
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

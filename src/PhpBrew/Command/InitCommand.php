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
        $root = Config::getPhpbrewRoot();
        $home = Config::getPhpbrewHome();
        $buildDir = Config::getBuildDir();
        $buildPrefix = Config::getBuildPrefix();
        // $versionBuildPrefix = Config::getVersionBuildPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        if (!file_exists($root)) {
            mkdir($root, 0755, true);
        }

        touch( $root . DIRECTORY_SEPARATOR . '.metadata_never_index' ); // prevent spotlight index here
        if ($root != $home) {
            touch( $home . DIRECTORY_SEPARATOR . '.metadata_never_index' );
        }

        if ($this->options->{'config'} !== null) {
            copy($this->options->{'config'}, $root . DIRECTORY_SEPARATOR . 'config.yaml');
        }

        if (!file_exists($home)) {
            mkdir($home, 0755, true);
        }
        if (!file_exists($buildPrefix)) {
            mkdir($buildPrefix, 0755, true);
        }
        if (!file_exists($buildDir)) {
            mkdir($buildDir, 0755, true);
        }

        // write bashrc script to phpbrew home
        file_put_contents($home . '/bashrc' , $this->getBashScript());

        echo <<<EOS
Phpbrew environment is initialized, required directories are created under

    $home

Paste the following line(s) to the end of your ~/.bashrc and start a
new shell, phpbrew should be up and fully functional from there:

    source $home/bashrc

To enable PHP version info in your shell prompt, please set PHPBREW_SET_PROMPT=1
in your `~/.bashrc` before you source `~/.phpbrew/bashrc`

    export PHPBREW_SET_PROMPT=1

For further instructions, simply run `phpbrew` to see the help message.

Enjoy phpbrew at \$HOME!!

EOS;

    }

    public function getBashScript()
    {
        $path = Phar::running() ?: __DIR__ . '/../../../shell';
        return file_get_contents($path . '/bashrc');
    }
}

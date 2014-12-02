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
        $buildPrefix = Config::getInstallPrefix();
        // $versionBuildPrefix = Config::getVersionInstallPrefix($version);
        // $versionBinPath     = Config::getVersionBinPath($version);

        if (!file_exists($root)) {
            mkdir($root, 0755, true);
        }

        $paths = array();
        $paths[] = $home;
        $paths[] = $root;
        $paths[] = $buildDir;
        $paths[] = $buildPrefix;
        foreach($paths as $p) {
            $this->logger->info("Checking directory $p");
            if (!file_exists($p)) {
                $this->logger->info("Creating directory $p");
                mkdir($p, 0755, true);
            }
        }

        $this->logger->info('Creating .metadata_never_index to prevent SpotLight indexing');
        touch($root . DIRECTORY_SEPARATOR . '.metadata_never_index'); // prevent spotlight index here
        touch($home . DIRECTORY_SEPARATOR . '.metadata_never_index' );

        if ($configFile = $this->options->{'config'}) {
            if (!file_exists($configFile)) {
                return $this->logger->error("$configFile does not exist.");
            }
            $this->logger->debug("Using yaml config from $configFile");
            copy($configFile, $root . DIRECTORY_SEPARATOR . 'config.yaml');
        }

        $this->logger->writeln( $this->formatter->format("Initialization successfully finished!",'strong_green') );
        $this->logger->writeln( $this->formatter->format("<=====================================================>", 'strong_white') );

        // write bashrc script to phpbrew home
        file_put_contents($home . '/bashrc' , $this->getBashScript());
        // write phpbrew.fish script to phpbrew home
        file_put_contents($home . '/phpbrew.fish' , $this->getFishScript());

        if (strpos(getenv("SHELL"), "fish") !== false)  {
            $initConfig = <<<EOS
Paste the following line(s) to the end of your ~/.config/fish/config.fish and start a
new shell, phpbrew should be up and fully functional from there:

    source $home/phpbrew.fish
EOS;
        }else {
            $initConfig = <<<EOS
Paste the following line(s) to the end of your ~/.bashrc and start a
new shell, phpbrew should be up and fully functional from there:

    source $home/bashrc

To enable PHP version info in your shell prompt, please set PHPBREW_SET_PROMPT=1
in your `~/.bashrc` before you source `~/.phpbrew/bashrc`

    export PHPBREW_SET_PROMPT=1
EOS;
        }

        echo <<<EOS
Phpbrew environment is initialized, required directories are created under

    $home

$initConfig

For further instructions, simply run `phpbrew` to see the help message.

Enjoy phpbrew at \$HOME!!


EOS;
        $this->logger->writeln( $this->formatter->format("<=====================================================>", 'strong_white') );
    }

    public function getBashScript()
    {
        $path = Phar::running() ?: __DIR__ . '/../../../shell';
        return file_get_contents($path . '/bashrc');
    }

    public function getFishScript()
    {
        $path = Phar::running() ?: __DIR__ . '/../../../shell';
        return file_get_contents($path . '/phpbrew.fish');
    }
}

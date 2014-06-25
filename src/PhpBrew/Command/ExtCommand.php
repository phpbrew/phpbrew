<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\Utils;
use CLIFramework\Command;

class ExtCommand extends Command
{
    /**
     * @var \CLIFramework\Logger
     */
    protected $logger = null;

    public function usage()
    {
        return "    phpbrew ext [install|enable|disable]";
    }

    public function brief()
    {
        return 'List extensions or execute extension subcommands';
    }

    public function init()
    {
        // $this->registerCommand('enable','PhpBrew\\Command\\ExtCommand\\EnableCommand');
        $this->registerCommand('enable');
        $this->registerCommand('install');
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('v|php:', 'The php version for which we install the module.');
    }

    public function execute()
    {
        if ($this->options->{'php'} !== null) {
            $php = Utils::findLatestPhpVersion($this->options->{'php'});
        } else {
            $php = Config::getCurrentPhpName();
        }

        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';

        // listing all local extensions
        if (version_compare(phpversion(), $php, '==')) {
            $loaded = array_map('strtolower', get_loaded_extensions());
        } else {
            $this->logger->info('PHP version is different from current active version.');
            $this->logger->info('Only available extensions are listed.');
            $this->logger->info('You will not see which of them are loaded.');
            $loaded = array();
        }

        // list for extensions which are not enabled
        $extensions = array();

        if (is_dir($extDir)) {
            $fp = opendir($extDir);

            if ($fp !== false) {
                while ($file = readdir($fp)) {
                    if ($file === '.' || $file === '..' || is_file($extDir . DIRECTORY_SEPARATOR . $file)) {
                        continue;
                    }

                    $n = strtolower(preg_replace('#-[\d\.]+$#', '', $file));

                    if (in_array($n, $loaded)) {
                        continue;
                    }

                    $extensions[] = $n;
                }

                sort($loaded);
                sort($extensions);

                closedir($fp);
            }
        }

        foreach ($loaded as $ext) {
            $this->logger->info('Loaded extensions:');
            $this->logger->info("  [*] $ext");
        }

        foreach ($extensions as $ext) {
            $this->logger->info('Available extensions:');
            $this->logger->info("  [ ] $ext");
        }
    }
}

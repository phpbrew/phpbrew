<?php
namespace PhpBrew\Command;

use PhpBrew\Config;
use PhpBrew\Utils;
use CLIFramework\Command;
use GetOptionKit\OptionCollection;

class ExtCommand extends Command
{

    public function usage()
    {
        return 'phpbrew ext [install|enable|disable|config]';
    }

    public function brief()
    {
        return 'List extensions or execute extension subcommands';
    }

    public function init()
    {
        $this->command('enable');
        $this->command('install');
        $this->command('disable');
        $this->command('config');
        $this->command('clean');
    }

    /**
     * @param GetOptionKit\OptionCollection $opts
     */
    public function options($opts)
    {
        $opts->add('v|php:', 'The php version for which we install the module.');
    }

    public function execute()
    {
        $buildDir = Config::getCurrentBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . 'ext';
        $loaded = array_map('strtolower', get_loaded_extensions());

        // list for extensions which are not enabled
        $extensions = array();

        if (is_dir($extDir)) {
            $fp = opendir($extDir);

            if ($fp !== false) {
                while ($file = readdir($fp)) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (is_file($extDir . '/' . $file)) {
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

        $this->logger->info('Loaded extensions:');
        foreach ($loaded as $ext) {
            $this->logger->info("  [*] $ext");
        }

        $this->logger->info('Available local extensions:');

        foreach ($extensions as $ext) {
            $this->logger->info("  [ ] $ext");
        }
    }
}

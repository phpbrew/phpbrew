<?php
namespace PhpBrew\Tasks;

use PhpBrew\Config;

/**
 * Task to run `make clean`
 */
class DefaultConfigTask extends BaseTask
{
    public function setup($options)
    {
        $phpConfigFile = $options->production ? 'php.ini-production' : 'php.ini-development';

        $this->logger->info(
            "---> Copying config file for " . $options->production ? 'production' : 'development'
        );

        if (file_exists($phpConfigFile)) {
            $this->logger->info("Found config file: $phpConfigFile");

            if (!file_exists(Config::getVersionEtcPath($version))) {
                $dir = Config::getVersionEtcPath($version);
                $this->logger->debug("Creating config directory");
                mkdir($dir, 0755, true);
            }

            $targetConfigPath = Config::getVersionEtcPath($version) . DIRECTORY_SEPARATOR . 'php.ini';

            if (file_exists($targetConfigPath)) {
                $this->logger->notice("$targetConfigPath exists, do not overwrite.");
            } else {
                // TODO: Move this to PhpConfigPatchTask
                // move config file to target location
                copy($phpConfigFile, $targetConfigPath);

                // replace current timezone
                $timezone = ini_get('date.timezone');
                $pharReadonly = ini_get('phar.readonly');

                if ($timezone || $pharReadonly) {
                    // patch default config
                    $content = file_get_contents($targetConfigPath);
                    if ($timezone) {
                        $this->logger->info("---> Found date.timezone, patch config timezone with $timezone");
                        $content = preg_replace('/^date.timezone\s*=\s*.*/im', "date.timezone = $timezone", $content);
                    }

                    if (!$pharReadonly) {
                        $this->logger->info("---> Disable phar.readonly option.");
                        $content = preg_replace('/^phar.readonly\s*=\s*.*/im', "phar.readonly = 0", $content);
                    }
                    file_put_contents($targetConfigPath, $content);
                }
            }
        }
    }

}

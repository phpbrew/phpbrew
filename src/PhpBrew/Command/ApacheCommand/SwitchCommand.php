<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 4/30/2016
 * Time: 12:32 PM
 */

namespace PhpBrew\Command\ApacheCommand;

use Exception;
use PhpBrew\Config;
use PhpBrew\Utils;
use PhpBrew\Utils\ApacheUtils;

class SwitchCommand extends BaseCommand
{
    private $usePhpbrewApacheConfig;

    public function usage()
    {
        return 'phpbrew apache switch [php version]';
    }

    public function brief()
    {
        return 'switch version for apache2';
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('apxs2', 'the path to apxs');
    }

    public function arguments($args)
    {
        $args->add('version');
    }

    public function prepare()
    {
        parent::prepare();

        $configPath = Config::getPhpbrewConfigDir() . DIRECTORY_SEPARATOR . 'apache.conf';

        $this->usePhpbrewApacheConfig = file_exists($configPath);

        return true;
    }


    public function execute($version)
    {
        if ($this->usePhpbrewApacheConfig) {
            //not implement yet.
        } else {
            $apxs = $this->options->apxs2;
            $apxs = ApacheUtils::getExecutableApxs($apxs);

            //check whether libphpXXXX.so exists
            $libdir = ApacheUtils::getModuleDir($apxs, ApacheUtils::PERMISSION_READ) . DIRECTORY_SEPARATOR;
            $module = $libdir . 'libphp' . $version . '.';
            if (file_exists($module . 'so')) {
                $module .= 'so';
            } elseif (file_exists($module . 'la')) {
                $module .= 'la';
            } else {
                $this->logger->warn("Fail to find module file of php $version. You need to make sure build php with --apxs2");
                throw new Exception;
            }

            //comment all php modules in httpd.conf
            $configPath = ApacheUtils::getApacheConfigPath();
            if (!is_writable($configPath)) {
                $this->logger->error("Apache config file ($configPath) is not writeable");
                throw new Exception;
            }
            $config = file_get_contents($configPath);
            file_put_contents($configPath . '.bak', $config);
            $config = preg_replace('/^(\s*LoadModule\s+php[57]_module\s+\S+)$/im', '#\1', $config);
            file_put_contents($configPath, $config);

            //add/active selected version
            $name = sprintf("php%d", substr($version, 0, 1));
            $this->logger->debug("enable $module via apxs($apxs)");
            Utils::pipeExecute("$apxs -e -n $name -a $module");
        }
        $this->logger->info("Version switch successfully. You need to restart apache manually.");
    }
}
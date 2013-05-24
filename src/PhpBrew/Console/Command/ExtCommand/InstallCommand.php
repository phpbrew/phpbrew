<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Utils;
use PhpBrew\Config;
use Exception;

class InstallCommand extends \CLIFramework\Command
{
    public function usage() 
    {
        return 'phpbrew [-dv] ext install [extension name] [-- [options....]]'; 
    }

    public function brief()
    {
        return 'Install PHP extension';
    }

    public function execute($extname, $version = 'stable')
    {
        $args = func_get_args();
        $options = array();
        if( ($pos = array_search('--',$args)) !== false ) {
            $options = array_slice($args,$pos + 1);
        }

        // preventing `phpbrew ext install yaml -- --with-yaml=/opt/local`
        if( $version == '--' ) {
            $version = 'stable';
        }

        $logger = $this->getLogger();
        $php = Config::getCurrentPhpName();
        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';

        // Install local extension
        $path = $extDir . DIRECTORY_SEPARATOR . $extname;
        if( file_exists( $path ) ) {

            $this->logger->info("===> Installing $extname extension...");
            $this->logger->debug("Extension path $path");

            $installer = new \PhpBrew\ExtensionInstaller($this->logger);
            $installer->runInstall($extname,$path,$options);

            $this->logger->info('===> Enabling extension');
            Utils::enable_extension($extname);

            $this->logger->info("Done");
        } else {
            chdir($extDir);

            $installer = new \PhpBrew\ExtensionInstaller($this->logger);
            $installedSo = $installer->installFromPecl($extname, $version ,$options);

            $this->logger->info('===> Enabling extension');

            $zendExtensions = array('xdebug','opcache','xhprof');

            $configFile = Utils::enable_extension($extname, in_array($extname,$zendExtensions) ? $installedSo : '');
            if ( $configFile ) {
                $this->logger->debug($configFile . ' is created.');
            }

            $this->logger->info("Done");
        }
    }
}

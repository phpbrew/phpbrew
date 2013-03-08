<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\Utils;
use CLIFramework\Command;

class InstallExtCommand extends Command
{

    public function brief() { return 'install extension for current PHP.'; }

    public function execute($extname, $version = 'stable')
    {
        $args = func_get_args();
        $options = array();
        if( ($pos = array_search('--',$args)) !== false ) {
            $options = array_slice($args,$pos + 1);
        }


        $logger = $this->getLogger();
        $php = Config::getCurrentPhpName();
        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';

        // listing all local extensions
        if( ! $extname ) {
            $loaded = array_map( 'strtolower' , get_loaded_extensions());

            $logger->info("Available extensions:");
            $fp = opendir( $extDir );
            $exts = array();
            while( $file = readdir($fp) ) {
                if( $file == '.' || $file == '..' )
                    continue;
                if( in_array($file,$loaded) ) {
                    echo "  [*] $file";
                } else {
                    echo "  [ ] $file";
                }
                $exts[] = $file;
            }
            closedir($fp);
            return;
        }

        // Install local extension
        $path = $extDir . DIRECTORY_SEPARATOR . $extname;
        if( file_exists( $path ) ) {

            $this->logger->info("===> Installing $extname extension...");
            $this->logger->debug("Extension path $path");

            $installer = new \PhpBrew\ExtensionInstaller($this->logger);
            $installer->runInstall($extname,$path,$options);

            $this->logger->info('===> enabling extension');
            Utils::create_extension_config($extname);

            $this->logger->info("Done");
        } else {
            $installer = new \PhpBrew\ExtensionInstaller($this->logger);
            $installer->installFromPecl($extname,'stable',$options);
        }
    }
}





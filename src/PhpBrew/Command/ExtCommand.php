<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\Utils;
use CLIFramework\Command;

class ExtCommand extends Command
{
    public function brief()
    {
        return 'Extension commands';
    }

    public function execute()
    {
        $php = Config::getCurrentPhpName();
        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';

        // listing all local extensions
        $loaded = array_map( 'strtolower' , get_loaded_extensions());

        $this->logger->info("Available extensions:");
        $fp = opendir( $extDir );
        $loadedExts = array();
        $exts = array();
        while( $file = readdir($fp) ) {
            if( $file == '.' || $file == '..' )
                continue;
            if( in_array($file,$loaded) ) {
                $loadedExts[] = $file;
            } else {
                $exts[] = $file;
            }
        }
        foreach( $loadedExts as $ext ) {
            $this->logger->info("  [*] $ext");
        }
        foreach( $exts as $ext ) {
            $this->logger->info("  [ ] $ext");
        }
        closedir($fp);
    }
}






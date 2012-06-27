<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\Utils;
use CLIFramework\Command;

class InstallExtCommand extends Command
{

    public function brief() { return 'install extension for current PHP.'; }

    public function execute($extname = null)
    {
        $logger = $this->getLogger();
        $php = Config::getCurrentPhp();
        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';

        if( ! $extname ) {
            $loaded = array_map( 'strtolower' , get_loaded_extensions());

            $logger->info("Available extensions:");
            $fp = opendir( $extDir );
            $exts = array();
            while( $file = readdir($fp) ) {
                if( $file == '.' || $file == '..' )
                    continue;

                if( in_array($file,$loaded) )
                    continue;

                $exts[] = $file;
            }
            closedir($fp);
            $logger->info("\t" . join(', ', $exts));
            return;
        }

        $path = $extDir . DIRECTORY_SEPARATOR . $extname;
        if( file_exists( $path ) ) {
            $logger->info("Extension path $path");

            chdir( $path );
            $logger->info("Installing $extname extension...");

            $logger->info("===> phpize...");
            system('phpize > build.log') !== false or die('Failed.');
            $args = func_get_args();
            if( count($args) )
                array_shift( $args );

            $logger->info('===> configuring...');
            system('./configure ' . join(' ',$args) . ' >> build.log' )
                !== false or die('Configure failed.');

            $logger->info('===> building...');
            system('make >> build.log') !== false or die('Build failed.');

            $logger->info('===> installing...');
            system('make install') !== false or die('Install failed.');

            $logger->info('===> enabling extension');

            Utils::create_extension_config($extname);

            $logger->info("Done");
        } else {
            $logger->info("Extension not found.");
        }
    }
}





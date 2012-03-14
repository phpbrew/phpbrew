<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use CLIFramework\Command;

class InstallExtCommand extends Command
{

    public function brief() { return 'install extension for current PHP.'; }

    public function execute($extname)
    {
        $logger = $this->getLogger();
        $php = Config::getCurrentPhp();
        $buildDir = Config::getBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext';

        $path = $extDir . DIRECTORY_SEPARATOR . $extname;
        if( file_exists( $path ) ) {
            chdir( $path );

            $logger->info("Installing $extname extension...");

            $logger->info("===> phpize...");
            system('phpize > build.log');
            $args = func_get_args();
            if( count($args) )
                array_shift( $args );

            $logger->info('===> configuring...');
            system('./configure ' . join(' ',$args) . ' >> build.log' ) !== false or die('Configure failed.');

            $logger->info('===> building...');
            system('make >> build.log') !== false or die('Build failed.');

            $logger->info('===> installing...');
            system('make install') !== false or die('Install failed.');
        }
        else {
            $logger->error( "$path not found." );

            $logger->info("Available extensions:");
            $fp = opendir( $extDir );
            $exts = array();
            while( $file = readdir($fp) ) {
                if( $file == '.' || $file == '..' )
                    continue;
                $exts[] = $file;
            }
            closedir($fp);
            $logger->info("\t" . join(', ', $exts));
        }

    }
}





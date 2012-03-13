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
        $path = $buildDir . DIRECTORY_SEPARATOR . $php . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . $extname;
        if( file_exists( $path ) ) {
            chdir( $path );

            $logger->info("===> phpize...");
            system('phpize > build.log');
            $args = func_get_args();
            if( count($args) )
                array_shift( $args );

            $logger->info("===> configuring...");
            system('./configure ' . join(' ',$args) . ' >> build.log' );

            $logger->info("===> building...");
            system('make >> build.log');

            $logger->info("===> installing...");
            system('make install');
        }
        else {
            $logger->error( "$path not found." );
        }

    }
}





<?php
namespace PhpBrew\Command\ExtCommand;
use PhpBrew\Config;
use PhpBrew\Extension;

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

        (new Extension($extname, $this->logger))->install($version, $options);
    }
}

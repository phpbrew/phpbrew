<?php
namespace PhpBrew;

class Utils
{

    static function find_include_path($hfile)
    {
        $prefixes = array('/usr', '/opt', '/usr/local', '/opt/local' );
        foreach( $prefixes as $prefix ) {
            $p = $prefix . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . $hfile;
            if( file_exists($p) )
                return $prefix;
        }
    }

    static function get_pkgconfig_prefix($package)
    {
        $cmd = 'pkg-config --variable=prefix ' . $package;
        $process = new Process( $cmd );
        $process->run();
        return trim($process->getOutput());
    }

    static function system($command)
    {
        system( $command ) !== false 
            or die('execute fail.');
    }

}





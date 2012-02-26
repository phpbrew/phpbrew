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

    static function system($command, $msg = 'execute fail')
    {
        system( $command ) !== false 
            or die($msg);
    }

    static function findbin($bin)
    {
        $path = getenv('PATH');
        $paths = explode( PATH_SEPARATOR , $path );
        foreach( $paths as $path ) {
            $f = $path . DIRECTORY_SEPARATOR . $bin;
            if( file_exists($f) ) {
                return $f;
            }
        }
    }

}





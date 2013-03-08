<?php
namespace PhpBrew;

class Utils
{

    static function get_extension_config_path($extname)
    {
        // create extension config file
        $path = Config::getCurrentPhpConfigScanPath() . DIRECTORY_SEPARATOR . $extname . '.ini';
        if ( ! file_exists( dirname($path) ) ) {
            mkdir($path,0755,true);
        }
        return $path;
    }

    static function enable_extension($extname, $zendpath = '')
    {
        // create extension config file
        $configPath = self::get_extension_config_path($extname);

        if ( file_exists($configPath) ) {
            $lines = file($configPath);
            foreach( $lines as &$line ) {
                if ( preg_match('#^;\s*((?:zend_)?extension\s*=.*)#', $line, $regs ) ) {
                    $line = $regs[1];
                }
            }
            file_put_contents($configPath, join("\n", $lines) );
            return $configPath;
        } else {
            if( $zendpath ) {
                $content = "zend_extension=$zendpath";
            } else {
                $content = "extension=$extname.so";
            }
            file_put_contents($configPath,$content);
            return $configPath;
        }
        return false;
    }

    static function find_include_path($hfile)
    {
        $prefixes = array('/usr', '/opt', '/usr/local', '/opt/local' );
        foreach( $prefixes as $prefix ) {
            $dir = $prefix . DIRECTORY_SEPARATOR . 'include';
            $path = $dir . DIRECTORY_SEPARATOR . $hfile;
            if( file_exists($path) )
                return $dir;
        }

    }


    static function find_include_prefix($hfile)
    {
        // TODO: phpbrew can be smarter (add brew path for detection here)
        $prefixes = array(
            '/usr',
            '/opt', 
            '/usr/local', 
            '/opt/local',
        );
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


    static function pipe_execute($command)
    {
        $proc = proc_open( $command , array(
                array("pipe","r"), // stdin
                array("pipe","w"), // stdout
                array("pipe","w"), // stderr
            ), $pipes);
        return stream_get_contents($pipes[1]);
    }
}





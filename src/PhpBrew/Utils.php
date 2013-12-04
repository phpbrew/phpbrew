<?php
namespace PhpBrew;
use Exception;

class Utils
{

    static function support_64bit()
    {
        $int = "9223372036854775807";
        $int = intval($int);
        if ($int == 9223372036854775807) {
            /* 64bit */
            return true;
        }
        elseif ($int == 2147483647) {
            /* 32bit */
            return false;
        }
        else {
            return false;
        }
    }

    static function get_extension_config_path($extname)
    {
        // create extension config file
        $path = Config::getCurrentPhpConfigScanPath() . DIRECTORY_SEPARATOR . $extname . '.ini';
        if ( ! file_exists( dirname($path) ) ) {
            mkdir(dirname($path),0755,true);
        }
        return $path;
    }

    static function enable_extension($extname, $zendpath = '')
    {
        $extname = strtolower($extname);
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


    /**
     * Find bin from prefix list
     */
    static function find_bin_by_prefix($bin)
    {
        $prefixes = self::get_lookup_prefixes();
        foreach( $prefixes as $prefix ) {
            $binpath = $prefix . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $bin;
            if ( file_exists($binpath) ) {
                return $binpath;
            }
            $binpath = $prefix . DIRECTORY_SEPARATOR . 'sbin' . DIRECTORY_SEPARATOR . $bin;
            if ( file_exists($binpath) ) {
                return $binpath;
            }
        }
    }


    static function get_lookup_prefixes() 
    {
        $prefixes = array(
            '/usr',
            '/usr/local',
            '/opt', 
            '/opt/local',
        );
        if ( $pathstr = getenv('PHPBREW_LOOKUP_PREFIX') ) {
            $paths = explode(':', $pathstr);
            array_splice($prefixes,0,0, $paths);
        }
        return $prefixes;
    }

    

    /**
     * Return the actual header file path from the lookup prefixes.
     *
     * @param string $hfile the header file name
     * @return string full qualified header file path
     */
    static function find_include_path()
    {
        $files = func_get_args();
        $prefixes = self::get_lookup_prefixes();
        foreach( $prefixes as $prefix ) {
            foreach( $files as $file ) {
                $dir = $prefix . DIRECTORY_SEPARATOR . 'include';
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if ( file_exists($path) ) {
                    return $dir;
                }
            }
        }
    }

    static function find_lib_prefix() {
        $files = func_get_args();
        $prefixes = self::get_lookup_prefixes();
        foreach( $prefixes as $prefix ) {
            foreach( $files as $file ) {
                $p = $prefix . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $file;
                if ( file_exists($p) ) {
                    return $prefix;
                }
            }
        }
    }

    static function find_include_prefix()
    {
        $files = func_get_args();
        $prefixes = self::get_lookup_prefixes();
        foreach( $prefixes as $prefix ) {
            foreach( $files as $file ) {
                if ( file_exists($prefix . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . $file) ) {
                    return $prefix;
                }
            }
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
        $lastline = system( $command, $retval );
        if ( $retval != 0 ) {
            throw new Exception($lastline);
        }
    }

    static function findbin($bin)
    {
        $path = getenv('PATH');
        $paths = explode( PATH_SEPARATOR , $path );
        foreach( $paths as $path ) {
            $f = $path . DIRECTORY_SEPARATOR . $bin;
            if( file_exists($f) ) {
                while ( is_link($f) ) {
                    $f = readlink($f);
                }
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





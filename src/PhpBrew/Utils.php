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


    static function find_libdir()
    {
        $prefixes = array(
            '/opt',
            '/opt/local',
            '/usr',
            '/usr/local',
        );
        if ( $pathstr = getenv('PHPBREW_LOOKUP_PREFIX') ) {
            $paths = explode(':', $pathstr);
            foreach( $paths as $path ) {
                $prefixes[] = $path;
            }
        }
        $prefixes = array_reverse($prefixes);

        foreach( $prefixes as $prefix ) {
            if ( file_exists("$prefix/lib/x86_64-linux-gnu") ) {
                return "lib/x86_64-linux-gnu";
            } else if ( file_exists("$prefix/lib/i386-linux-gnu") ) {
                return "lib/i386-linux-gnu";
            }
        }
    }

    static function get_lookup_prefixes() 
    {
        $prefixes = array(
            '/opt',
            '/opt/local',
            '/usr',
            '/usr/local',
        );

        if ( $pathstr = getenv('PHPBREW_LOOKUP_PREFIX') ) {
            $paths = explode(':', $pathstr);
            foreach( $paths as $path ) {
                $prefixes[] = $path;
            }
        }

        // if there is lib path, insert it to the end.
        foreach( $prefixes as $prefix ) {
            if ( file_exists("$prefix/lib/x86_64-linux-gnu") ) {
                $prefixes[] = "$prefix/lib/x86_64-linux-gnu";
            } else if ( file_exists("$prefix/lib/i386-linux-gnu") ) {
                $prefixes[] = "$prefix/lib/i386-linux-gnu";
            } 
        }
        return array_reverse($prefixes);
    }

    

    /**
     * Return the actual header file path from the lookup prefixes.
     *
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

        return null;
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

        return null;
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

        return null;
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

    /**
     * Find executable binary by PATH environment.
     *
     * @param string $bin binary name
     * @return string the path
     */
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

    static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    static function findLatestPhpVersion($version = null)
    {
        $foundVersion = false;
        $buildDir = Config::getBuildDir();
        $hasPrefix = self::startsWith($version, 'php-');

        if (is_dir($buildDir)) {
            if ($hasPrefix == true) {
                $version = str_replace('php-', '', $version);
            }

            $fp = opendir($buildDir);

            if ($fp !== false) {
                while($file = readdir($fp)) {
                    if ($file === '.'
                        || $file === '..'
                        || is_file($buildDir . DIRECTORY_SEPARATOR . $file)
                    ) {
                        continue;
                    }

                    $curVersion = strtolower(preg_replace('/^[\D]*-/', '', $file));

                    if (self::startsWith($curVersion, $version) && version_compare($curVersion, $version, '>=')) {
                        $foundVersion = $curVersion;

                        if (version_compare($foundVersion, $version, '=')) {
                            break;
                        }
                    }
                }

                closedir($fp);
            }

            if ($hasPrefix == true && $foundVersion !== false) {
                $foundVersion = 'php-'.$foundVersion;
            }
        }

        return $foundVersion;
    }
}





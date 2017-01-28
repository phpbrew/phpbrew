<?php

namespace PhpBrew;

use CLIFramework\Logger;
use PhpBrew\Exception\SystemCommandException;
use PhpBrew\Platform\PlatformInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Utils
{
    public static function readTimeZone()
    {
        if (is_readable($tz = '/etc/timezone')) {
            $lines = array_filter(file($tz), function ($line) {
                return !preg_match('/^#/', $line);
            });
            if (!empty($lines)) {
                return trim($lines[0]);
            }
        }

        return false;
    }

    public static function canonicalizeBuildName($version)
    {
        if (!preg_match('/^php-/', $version)) {
            return 'php-'.$version;
        }

        return $version;
    }

    public static function support64bit()
    {
        $int = '9223372036854775807';
        $int = intval($int);
        if ($int == 9223372036854775807) {
            /* 64bit */

            return true;
        } elseif ($int == 2147483647) {
            /* 32bit */

            return false;
        } else {
            return false;
        }
    }

    /**
     * Find bin from prefix list.
     *
     * @param string $bin
     *
     * @return string|null
     */
    public static function findBinByPrefix($bin)
    {
        $prefixes = self::getLookupPrefixes();

        foreach ($prefixes as $prefix) {
            $binPath = $prefix.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.$bin;

            if (file_exists($binPath)) {
                return $binPath;
            }

            $binPath = $prefix.DIRECTORY_SEPARATOR.'sbin'.DIRECTORY_SEPARATOR.$bin;

            if (file_exists($binPath)) {
                return $binPath;
            }
        }

        return;
    }

    public static function findLibDir()
    {
        $prefixes = array(
            '/opt',
            '/opt/local',
            '/usr',
            '/usr/local',
        );

        if ($pathStr = getenv('PHPBREW_LOOKUP_PREFIX')) {
            $paths = explode(':', $pathStr);

            foreach ($paths as $path) {
                $prefixes[] = $path;
            }
        }

        $prefixes = array_reverse($prefixes);

        foreach ($prefixes as $prefix) {
            if ($arch = self::detectArch($prefix)) {
                return "lib/$arch";
            }
        }

        return;
    }

    public static function detectArch($prefix)
    {
        /*
            Prioritizes the FHS compliant
            /usr/lib/i386-linux-gnu/
            /usr/include/i386-linux-gnu/
            /usr/lib/x86_64-linux-gnu/
            /usr/local/lib/powerpc-linux-gnu/
            /usr/local/include/powerpc-linux-gnu/
            /opt/foo/lib/sparc-solaris/
            /opt/bar/include/sparc-solaris/
         */
        $multiArchs = array(
            'lib/lib64',
            'lib/lib32',
            'lib64', // Linux Fedora
            'lib', // CentOS
            'lib/ia64-linux-gnu', // Linux IA-64
            'lib/x86_64-linux-gnu', // Linux x86_64
            'lib/x86_64-kfreebsd-gnu', // FreeBSD
            'lib/i386-linux-gnu',
        );
        foreach ($multiArchs as $archName) {
            if (file_exists("$prefix/$archName")) {
                return $archName;
            }
        }

        return;
    }

    public static function getLookupPrefixes()
    {
        $prefixes = array(
            '/usr',
            '/usr/local',
            '/usr/local/opt', // homebrew link
            '/opt',
            '/opt/local',
        );

        if ($pathStr = getenv('PHPBREW_LOOKUP_PREFIX')) {
            $paths = explode(':', $pathStr);
            foreach ($paths as $path) {
                $prefixes[] = $path;
            }
        }

        // if there is lib path, insert it to the end.
        foreach ($prefixes as $prefix) {
            if ($arch = self::detectArch($prefix)) {
                $prefixes[] = "$prefix/$arch";
            }
        }

        return array_reverse($prefixes);
    }

    /**
     * Return the actual header file path from the lookup prefixes.
     *
     * @return string full qualified header file path
     */
    public static function findIncludePath()
    {
        $files = func_get_args();
        $prefixes = self::getLookupPrefixes();
        foreach ($prefixes as $prefix) {
            foreach ($files as $file) {
                $path = $prefix.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.$file;
                if (file_exists($path)) {
                    return $prefix;
                }
            }
        }

        return;
    }

    public static function findLibPrefix()
    {
        $files = func_get_args();
        $prefixes = self::getLookupPrefixes();

        foreach ($prefixes as $prefix) {
            foreach ($files as $file) {
                $p = $prefix.DIRECTORY_SEPARATOR.$file;
                if (file_exists($p)) {
                    return $prefix;
                }
            }
        }

        return;
    }

    public static function findIncludePrefix()
    {
        $files = func_get_args();
        $prefixes = self::getLookupPrefixes();

        foreach ($prefixes as $prefix) {
            foreach ($files as $file) {
                $path = $prefix.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.$file;
                if (file_exists($path)) {
                    return $prefix;
                }
            }
        }

        return;
    }

    /**
     * @param string $package
     */
    public static function getPkgConfigPrefix($package)
    {
        if (self::findBin('pkg-config')) {
            $cmd = 'pkg-config --variable=prefix '.$package;
            $process = new Process($cmd);
            $code = $process->run();
            if (intval($code) === 0) {
                $path = trim($process->getOutput());
                if (file_exists($path)) {
                    return $path;
                }
            }

            return false;
        }

        return false;
    }

    public static function system($command, $logger = null, $build = null)
    {
        if (is_array($command)) {
            $command = implode(' ', $command);
        }

        if ($logger) {
            $logger->debug('Running Command:'.$command);
        }

        $lastline = system($command, $returnValue);
        if ($returnValue !== 0) {
            throw new SystemCommandException("Command failed: $command returns: $lastline", $build);
        }
        return $returnValue;
    }

    /**
     * Find executable binary by PATH environment.
     *
     * @param string $bin binary name
     *
     * @return string the path
     */
    public static function findBin($bin)
    {
        $path = getenv('PATH');
        $paths = explode(PATH_SEPARATOR, $path);

        foreach ($paths as $path) {
            $f = $path.DIRECTORY_SEPARATOR.$bin;
            // realpath will handle file existence or symbolic link automatically
            $f = realpath($f);
            if ($f !== false) {
                return $f;
            }
        }

        return;
    }

    public static function pipeExecute($command)
    {
        $proc = proc_open(
            $command,
            array(
                array('pipe', 'r'), // stdin
                array('pipe', 'w'), // stdout
                array('pipe', 'w'), // stderr
            ),
            $pipes
        );

        return stream_get_contents($pipes[1]);
    }

    public static function startsWith($haystack, $needle)
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }

    public static function endsWith($haystack, $needle)
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }

    public static function findLatestPhpVersion($version = null)
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
                while ($file = readdir($fp)) {
                    if ($file === '.'
                        || $file === '..'
                        || is_file($buildDir.DIRECTORY_SEPARATOR.$file)
                    ) {
                        continue;
                    }

                    $curVersion = strtolower(preg_replace('/^[\D]*-/', '', $file));

                    if (self::startsWith($curVersion, $version) && version_compare($curVersion, $foundVersion, '>=')) {
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

    public static function recursive_unlink($path, Logger $logger)
    {
        $directoryIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            $logger->debug('Deleting '.$file->getPathname());
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        if (is_dir($path)) {
            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    public static function editor($file)
    {
        $tty = exec('tty');
        $editor = escapeshellarg(getenv('EDITOR') ?: 'nano');
        exec("{$editor} {$file} > {$tty}");
    }

    public static function isRootlessEnabled()
    {
        return PlatformInfo::getInstance()->isMac() &&
            !is_writeable('/bin');
        // following directories are restricted if rootless enabled (osX 10.11+):
        // /System, /bin, /sbin, /usr (except /usr/bin)
    }
}

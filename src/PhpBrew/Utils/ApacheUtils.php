<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 4/30/2016
 * Time: 12:41 PM
 */

namespace PhpBrew\Utils;


use Exception;
use PhpBrew\Utils;

class ApacheUtils
{
    const PERMISSION_READ = 4;
    const PERMISSION_WRITE = 2;

    public static function getExecutableApxs($apxs = '')
    {
        // trying to find apxs binary in case it wasn't explicitly specified (+apxs variant without path)
        if (empty($apxs)) {
            $apxs = Utils::findbin('apxs');
            Utils::getGlobalLogger()->debug("Found apxs2 binary: $apxs");
        }

        if (!is_executable($apxs)) {
            throw new Exception("apxs binary is not executable: $apxs");
        }
        return $apxs;
    }

    public static function getExecuatableHttpd($path = '')
    {
        if (empty($path)) {
            $path = Utils::findbin('httpd');
            Utils::getGlobalLogger()->debug("Found httpd binary: $path");
        }

        if (empty($path)) { //for ubuntu
            $path = Utils::findbin('apache2');
            Utils::getGlobalLogger()->debug("Found apache2 binary: $path");
        }

        if (!is_executable($path)) {
            throw new Exception("httpd/apache2 binary is not executable: $path");
        }
        return $path;
    }

    public static function getApacheConfigPath()
    {
        $httpd = self::getExecuatableHttpd();
        $res = Utils::pipeExecute("$httpd -V | grep SERVER_CONFIG");
        $configPath = substr(trim(explode('=', $res)[1]), 1, -1);
        if ($configPath[0] == '/') {
            Utils::getGlobalLogger()->debug("Found apache config path: $configPath");
            return $configPath;
        } else {
            $res = Utils::pipeExecute("$httpd -S | grep ServerRoot");
            $serverRoot = substr(trim(explode(':', $res)[1]), 1, -1);
            $configPath = $serverRoot . DIRECTORY_SEPARATOR . $configPath;
            Utils::getGlobalLogger()->debug("Found apache config path: $configPath");
            return $configPath;
        }
    }

    public static function getModuleDir($apxs = '', $permission = self::PERMISSION_READ | self::PERMISSION_WRITE)
    {
        $logger = Utils::getGlobalLogger();
        $apxs = self::getExecutableApxs($apxs);

        $libdir = trim(Utils::pipeExecute("$apxs -q LIBEXECDIR"));
        // use apxs to check module dir permission
        if ($libdir) {
            if ($permission & self::PERMISSION_READ && false === is_readable($libdir)) {
                $logger->error("Apache module dir $libdir is not readable.\nPlease consider using chmod to change the folder permission:");
                $logger->error("    \$ sudo chmod -R oga+r $libdir");
                throw new Exception;
            }

            if ($permission & self::PERMISSION_WRITE && false === is_writable($libdir)) {
                $logger->error("Apache module dir $libdir is not writable.\nPlease consider using chmod to change the folder permission:");
                $logger->error("    \$ sudo chmod -R oga+rw $libdir");
                $logger->error("Warnings: the command above is not safe for public systems. please use with discretion.");
                throw new Exception;
            }
        } else {
            $logger->error('Fail to find apache module dir.');
            throw new Exception;
        }

        return $libdir;
    }
}
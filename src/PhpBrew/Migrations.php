<?php
namespace PhpBrew;

/**
 * Tasks to ease phpbrew backwards / forwards compatibility
 */
class Migrations
{
    /**
     * Creates extension config folder
     *
     * Creates var/db/ folder for current php version if necessary
     * to keep Compatibility with older versions of phpbrew
     */
    public static function setupConfigFolder()
    {
        $path = Config::getCurrentPhpConfigScanPath();
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
}

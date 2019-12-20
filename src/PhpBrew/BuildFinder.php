<?php

namespace PhpBrew;

class BuildFinder
{
    /**
     * @return string[] PHP builds
     */
    public static function findInstalledBuilds()
    {
        $path = Config::getRoot() . DIRECTORY_SEPARATOR . 'php';

        if (!file_exists($path)) {
            return array();
        }

        $names = array_filter(scandir($path), function ($name) use ($path) {
            return $name != '.'
                && $name != '..'
                && file_exists(
                    $path
                    . DIRECTORY_SEPARATOR . $name
                    . DIRECTORY_SEPARATOR . 'bin'
                    . DIRECTORY_SEPARATOR . 'php'
                );
        });

        uasort($names, 'version_compare'); // ordering version name ascending... 5.5.17, 5.5.12

        // make it descending... since there is no sort function for user-define in reverse order.
        return array_reverse($names);
    }

    /**
     * @return string[] PHP versions
     */
    public static function findInstalledVersions()
    {
        return array_map(function ($name) {
            return preg_replace('/^php-(?=(\d+\.\d+\.\d+(-dev|((alpha|beta|RC)\d+))?)$)/', '', $name);
        }, self::findInstalledBuilds());
    }
}

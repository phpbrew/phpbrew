<?php

namespace PhpBrew;

class BuildFinder
{
    /**
     * @return string[]
     */
    public static function findInstalledBuilds($stripPrefix = true)
    {
        $path = Config::getRoot().DIRECTORY_SEPARATOR.'php';
        if (!file_exists($path)) {
            throw new Exception($path.' does not exist.');
        }
        $names = scandir($path);
        $names = array_filter($names, function ($name) use ($path) {
            return $name != '.' && $name != '..' && file_exists($path.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'php');
        });

        if ($names == null || empty($names)) {
            return array();
        }

        if ($stripPrefix) {
            $names = array_map(function ($name) {
                return preg_replace('/^php-(?=(\d+\.\d+\.\d+((alpha|beta|RC)\d+)?)$)/', '', $name);
            }, $names);
        }
        uasort($names, 'version_compare'); // ordering version name ascending... 5.5.17, 5.5.12
        return array_reverse($names);  // make it descending... since there is no sort function for user-define in reverse order.
    }

    /**
     * @return string[] build names
     */
    public static function findMatchedBuilds($buildNameRE = '', $stripPrefix = true)
    {
        $builds = self::findInstalledBuilds($stripPrefix);

        return array_filter($builds, function ($build) use ($buildNameRE) {
            return preg_match("/^$buildNameRE/i", $build);
        });
    }

    /**
     * @return string[] build names
     */
    public static function findFirstMatchedBuild($buildNameRE = '', $stripPrefix = true)
    {
        $builds = self::findInstalledBuilds($stripPrefix);
        foreach ($builds as $build) {
            if (preg_match("/$buildNameRE/i", $build)) {
                return $build;
            }
        }
    }

    /**
     * @return string[] build names
     */
    public static function findLatestBuild($stripPrefix = true)
    {
        $builds = self::findInstalledBuilds($stripPrefix);
        if (!empty($builds)) {
            return $builds[0]; // latest
        }
    }
}

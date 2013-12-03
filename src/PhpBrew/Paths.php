<?php
namespace PhpBrew;

class Paths
{
    public static function getMacportsPrefix() {
        return "/opt/local";
    }

    public static function getHomebrewPrefix() {
        return "/usr/local";
    }

    public static function getDebianPrefix() {
        return "/usr";
    }
}




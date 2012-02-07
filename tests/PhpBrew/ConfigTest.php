<?php

class ConfigTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $versions = PhpBrew\Config::getInstalledPhpVersions();

        var_dump( $versions ); 
        
    }
}


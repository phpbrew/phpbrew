<?php

class ConfigTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $versions = PhpBrew\Config::getInstalledPhpVersions();
        // ok( $versions );
        // var_dump( $versions ); 
    }
}


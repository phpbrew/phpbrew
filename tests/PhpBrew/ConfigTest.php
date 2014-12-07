<?php

/**
 * @small
 */
class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $versions = PhpBrew\Config::getInstalledPhpVersions();
        // ok( $versions );
        // var_dump( $versions );
    }
}

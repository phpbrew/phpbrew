<?php

class PkgConfigTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $prefix = PhpBrew\PkgConfig::getPrefix('libcurl');
        ok( $prefix );
    }
}


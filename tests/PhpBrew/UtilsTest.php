<?php
use PhpBrew\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        Utils::support_64bit();
        ok(1);
    }
}


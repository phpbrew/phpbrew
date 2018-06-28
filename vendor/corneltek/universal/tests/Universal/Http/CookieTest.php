<?php

class CookieTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $cookie = new Universal\Http\Cookie;
        $cookie->set('test','test');
    }
}


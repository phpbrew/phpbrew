<?php
/*
 * This file is part of the {{ }} package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace Universal\Event;
use PHPUnit_Framework_TestCase;

class PhpEventTest extends PHPUnit_Framework_TestCase 
{
    function test() 
    {
        global $z;
        $e = PhpEvent::getInstance();
        $e->register( 'test', function($a,$b,$c) {
            global $z;
            $z = $a + $b + $c;
        });
        $e->trigger( 'test' , 1,2,3  );
        is( 6, $z );
    }
}


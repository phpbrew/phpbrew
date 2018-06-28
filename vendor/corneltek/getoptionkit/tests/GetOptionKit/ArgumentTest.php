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

use GetOptionKit\Argument;
class ArgumentTest extends PHPUnit_Framework_TestCase 
{
    function test()
    {
        $arg = new Argument( '--option' );
        ok( $arg->isLongOption() );
        not_ok( $arg->isShortOption() );
        is( 'option' , $arg->getOptionName() );
    }

    function test2()
    {
        $arg = new Argument( '--option=value' );
        ok( $arg->containsOptionValue() );
        is( 'value' , $arg->getOptionValue() );
        is( 'option' , $arg->getOptionName() );
    }

    function test3()
    {
        $arg = new Argument( '-abc' );
        ok( $arg->withExtraFlagOptions() );

        $args = $arg->extractExtraFlagOptions();
        ok( $args );
        count_ok( 2, $args );

        is( '-b', $args[0] );
        is( '-c', $args[1] );
        is( '-a', $arg->arg);
    }

    function testZeroValue()
    {
        $arg = new Argument( '0' );
        not_ok( $arg->isShortOption() );
        not_ok( $arg->isLongOption() );
        not_ok( $arg->isEmpty() );
    }
}



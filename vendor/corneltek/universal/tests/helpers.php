<?php
/*
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/* php 5.3.3 does not support this */
if( ! defined('DEBUG_BACKTRACE_PROVIDE_OBJECT') )
    define( 'DEBUG_BACKTRACE_PROVIDE_OBJECT' , 1 );

function ok( $v , $msg = null )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertTrue( $v ? true : false , $msg );
    return $v ? true : false;
}

function not_ok( $v , $msg = null )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertFalse( $v ? true : false , $msg );
    return $v ? true : false;
}

function is( $expected , $v , $msg = null )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertEquals( $expected , $v , $msg );
    return $expected === $v ? true : false;
}


function isa_ok( $expected , $v , $msg = null )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertInstanceOf( $expected , $v , $msg );
}

function is_class( $expected , $v , $msg = null )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertInstanceOf( $expected , $v , $msg );
}

function count_ok( $expected,$v, $msg = null ) 
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertCount( $expected , $v , $msg );
}

function skip( $msg )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->markTestSkipped( $msg );
}


function like( $e, $v , $msg = null )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertRegExp($e,$v,$msg);
}

function is_true($e,$v,$msg = null)
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertTrue($e,$v,$msg);
}

function is_false($e,$v,$msg= null)
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $testobj = $stacks[1]['object'];
    $testobj->assertFalse($e,$v,$msg);
}

function file_equals($e,$v,$msg = null)
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT );
    $testobj = $stacks[1]['object'];
    $testobj->assertFileEquals($e,$v,$msg);
}

function file_ok( $path , $msg = null ) {
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT );
    $testobj = $stacks[1]['object'];
    $testobj->assertFileExists($path , $msg );
    $testobj->assertTrue( is_file( $path ) , $msg );
}

function path_ok( $path , $msg = null ) {
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT );
    $testobj = $stacks[1]['object'];
    $testobj->assertFileExists($path , $msg );
}

function dump($e)
{
    var_dump($e);
    ob_flush();
}




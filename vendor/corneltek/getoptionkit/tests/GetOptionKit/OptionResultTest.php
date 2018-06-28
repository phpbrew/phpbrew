<?php
/*
 * This file is part of the GetOptionKit package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

class OptionResultTest extends PHPUnit_Framework_TestCase 
{

    function testOption()
    {
        $option = new \GetOptionKit\OptionResult;
        ok( $option );

        $specs = new \GetOptionKit\OptionCollection;
        $specs->add('name:','name');
        $result = \GetOptionKit\OptionResult::create($specs,array( 'name' => 'c9s' ),array( 'arg1' ));
        ok( $result );
        ok( $result->arguments );
        ok( $result->name );
        is( 'c9s', $result->name );
        is( $result->arguments[0] , 'arg1' );
    }

}



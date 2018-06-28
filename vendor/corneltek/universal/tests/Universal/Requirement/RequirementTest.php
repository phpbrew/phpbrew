<?php 




namespace Universal\Requirement;
use PHPUnit_Framework_TestCase;
use Exception;

class RequirementTest extends PHPUnit_Framework_TestCase
{
    function testFunc()
    {
        $require = new Requirement;
        ok( $require );
        $require->classes( 'Universal\Requirement\RequirementTest' );

        # ok( $require->extensions('apc') );
        ok( $require->extensions('curl') );

        ok( $require->php( '5.0.0' ) );
        ok( $require->php( '5.0' ) );
    }
}


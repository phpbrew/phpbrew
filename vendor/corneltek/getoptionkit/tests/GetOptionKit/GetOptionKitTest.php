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
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionParser;

class GetOptionKitTest extends PHPUnit_Framework_TestCase 
{
    public $parser;
    public $specs;

    public function setUp()
    {
        $this->specs = new OptionCollection;
        $this->parser = new OptionParser($this->specs);
    }

    public function testOptionWithNegativeValue() {
        $this->specs->add( 'n|nice:' , 'I take negative value' );
        $result = $this->parser->parse(array('-n', '-1'));
        $this->assertEquals(-1, $result->nice);
    }

    public function testOptionWithShortNameAndLongName() {
        $this->specs->add( 'f|foo' , 'flag' );
        $result = $this->parser->parse(array('-f'));
        $this->assertTrue($result->foo);

        $result = $this->parser->parse(array('--foo'));
        $this->assertTrue($result->foo);
    }

    public function testSpec()
    {
        $this->specs->add( 'f|foo:' , 'option require value' );
        $this->specs->add( 'b|bar+' , 'option with multiple value' );
        $this->specs->add( 'z|zoo?' , 'option with optional value' );
        $this->specs->add( 'v|verbose' , 'verbose message' );
        $this->specs->add( 'd|debug'   , 'debug message' );

        $spec = $this->specs->get('foo');
        $this->assertTrue($spec->isRequired());

        $spec = $this->specs->get('bar');
        $this->assertTrue( $spec->isMultiple() );

        $spec = $this->specs->get('zoo');
        $this->assertTrue( $spec->isOptional() );

        $spec = $this->specs->get( 'debug' );
        $this->assertNotNull( $spec );
        is_class( 'GetOptionKit\\Option', $spec );
        is( 'debug', $spec->long );
        is( 'd', $spec->short );
        $this->assertTrue( $spec->isFlag() );
    }

    public function testRequire()
    {
        $this->specs->add( 'f|foo:' , 'option require value' );
        $this->specs->add( 'b|bar+' , 'option with multiple value' );
        $this->specs->add( 'z|zoo?' , 'option with optional value' );
        $this->specs->add( 'v|verbose' , 'verbose message' );
        $this->specs->add( 'd|debug'   , 'debug message' );

        $firstExceptionRaised = false;
        $secondExceptionRaised = false;

        // option required a value should throw an exception
        try {
            $result = $this->parser->parse( array( '-f' , '-v' , '-d' ) );
        }
        catch (Exception $e) {
            $firstExceptionRaised = true;
        }

        // even if only one option presented in args array
        try {
            $result = $this->parser->parse(array('-f'));
        } catch (Exception $e) {
            $secondExceptionRaised = true;
        }

        if ($firstExceptionRaised && $secondExceptionRaised) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    function testMultiple()
    {
        $opt = new \GetOptionKit\GetOptionKit;
        ok( $opt );
        $opt->add( 'b|bar+' , 'option with multiple value' );
        $result = $opt->parse(explode(' ','-b 1 -b 2 --bar 3'));

        ok( $result->bar );
        count_ok(3,$result->bar);
    }

    public function testIntegerTypeNonNumeric()
    {
        $opt = new \GetOptionKit\GetOptionKit;
        ok( $opt );
        $opt->add( 'b|bar:=number' , 'option with integer type' );

        $spec = $opt->get('bar');
        ok( $spec->isTypeNumber() );

        // test non numeric
        try {
            $result = $opt->parse(explode(' ','-b test'));
            ok( $result->bar );
        } catch (Exception $e ) {
            ok( $e );
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }


    public function testIntegerTypeNumericWithoutEqualSign()
    {
        $opt = new \GetOptionKit\GetOptionKit;
        $opt->add( 'b|bar:=number' , 'option with integer type' );

        $spec = $opt->get('bar');
        $this->assertTrue($spec->isTypeNumber());

        $result = $opt->parse(explode(' ','-b 123123'));
        $this->assertNotNull($result);
        $this->assertEquals(123123, $result->bar);
    }

    public function testIntegerTypeNumericWithEqualSign()
    {
        $opt = new \GetOptionKit\GetOptionKit;
        $opt->add( 'b|bar:=number' , 'option with integer type' );

        $spec = $opt->get('bar');
        $this->assertTrue($spec->isTypeNumber());

        $result = $opt->parse(explode(' ','-b=123123'));
        $this->assertNotNull($result);
        $this->assertNotNull($result->bar);
        $this->assertEquals(123123, $result->bar);
    }

    public function testStringType()
    {
        $this->specs->add( 'b|bar:=string' , 'option with type' );

        $spec = $this->specs->get('bar');

        $result = $this->parser->parse(explode(' ','-b text arg1 arg2 arg3'));
        ok( $result->bar );

        $result = $this->parser->parse(explode(' ','-b=text arg1 arg2 arg3'));
        ok( $result->bar );

        $args = $result->getArguments();
        ok( $args );
        count_ok(3,$args);
        is( 'arg1', $args[0] );
        is( 'arg2', $args[1] );
        is( 'arg3', $args[2] );
    }


    public function testSpec2()
    {
        $this->specs->add( 'long'   , 'long option name only.' );
        $this->specs->add( 'a'   , 'short option name only.' );
        $this->specs->add( 'b'   , 'short option name only.' );

        ok( $this->specs->all() );
        ok( $this->specs );
        ok( $result = $this->parser->parse(explode(' ','-a -b --long')) );
        ok( $result->a );
        ok( $result->b );
    }

    public function testSpecCollection()
    {
        $this->specs->add( 'f|foo:' , 'option requires a value.' );
        $this->specs->add( 'b|bar+' , 'option with multiple value.' );
        $this->specs->add( 'z|zoo?' , 'option with optional value.' );
        $this->specs->add( 'v|verbose' , 'verbose message.' );
        $this->specs->add( 'd|debug'   , 'debug message.' );
        $this->specs->add( 'long'   , 'long option name only.' );
        $this->specs->add( 's'   , 'short option name only.' );

        ok( $this->specs->all() );
        ok( $this->specs );

        count_ok( 7 , $array = $this->specs->toArray() );
        $this->assertNotEmpty( isset($array[0]['long'] ));
        $this->assertNotEmpty( isset($array[0]['short'] ));
        $this->assertNotEmpty( isset($array[0]['desc'] ));
    }

    public function test()
    {
        $this->specs->add( 'f|foo:' , 'option require value' );
        $this->specs->add( 'b|bar+' , 'option with multiple value' );
        $this->specs->add( 'z|zoo?' , 'option with optional value' );
        $this->specs->add( 'v|verbose' , 'verbose message' );
        $this->specs->add( 'd|debug'   , 'debug message' );

        $result = $this->parser->parse( array( '-f' , 'foo value' , '-v' , '-d' ) );
        ok( $result );
        ok( $result->foo );
        ok( $result->verbose );
        ok( $result->debug );
        is( 'foo value', $result->foo );
        ok( $result->verbose );
        ok( $result->debug );

        $result = $this->parser->parse( array( '-f=foo value' , '-v' , '-d' ) );
        ok( $result );
        ok( $result->foo );
        ok( $result->verbose );
        ok( $result->debug );

        is( 'foo value', $result->foo );
        ok( $result->verbose );
        ok( $result->debug );

        $result = $this->parser->parse( array( '-vd' ) );
        ok( $result->verbose );
        ok( $result->debug );
    }


}

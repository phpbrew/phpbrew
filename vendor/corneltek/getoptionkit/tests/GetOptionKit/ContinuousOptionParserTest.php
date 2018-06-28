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

namespace tests\GetOptionKit;
use GetOptionKit\ContinuousOptionParser;
use GetOptionKit\OptionCollection;

class ContinuousOptionParserTest extends \PHPUnit_Framework_TestCase 
{

    public function testOptionCollection() 
    {
        $specs = new OptionCollection;
        $specVerbose = $specs->add('v|verbose');
        $specColor = $specs->add('c|color');
        $specDebug = $specs->add('d|debug');
    }


    /* test parser without options */
    public function testParseSubCommandOptions()
    {
        $appspecs = new OptionCollection;
        $appspecs->add('v|verbose');
        $appspecs->add('c|color');
        $appspecs->add('d|debug');

        $cmdspecs = new OptionCollection;
        $cmdspecs->add('as:');
        $cmdspecs->add('b');
        $cmdspecs->add('c');

        $parser = new ContinuousOptionParser( $appspecs );

        $subcommands = array('subcommand1','subcommand2','subcommand3');
        $subcommand_specs = array(
            'subcommand1' => clone $cmdspecs,
            'subcommand2' => clone $cmdspecs,
            'subcommand3' => clone $cmdspecs,
        );
        $subcommand_options = array();

        $argv = explode(' ','program -v -c subcommand1 --as 99 arg1 arg2 arg3');
        // $argv = explode(' ','program subcommand1 -a 1 subcommand2 -a 2 subcommand3 -a 3 arg1 arg2 arg3');
        $app_options = $parser->parse( $argv );
        $arguments = array();
        while (! $parser->isEnd()) {
            if (@$subcommands[0] && $parser->getCurrentArgument() == $subcommands[0] ) {
                $parser->advance();
                $subcommand = array_shift( $subcommands );
                $parser->setSpecs( $subcommand_specs[$subcommand] );
                $subcommand_options[ $subcommand ] = $parser->continueParse();
            } else {
                $arguments[] = $parser->advance();
            }
        }
        
        $this->assertEquals( 'arg1', $arguments[0] );
        $this->assertEquals( 'arg2', $arguments[1] );
        $this->assertEquals( 'arg3', $arguments[2] );
        $this->assertEquals( 99, $subcommand_options['subcommand1']->as );
    }



    public function testParser3()
    {
        $appspecs = new OptionCollection;
        $appspecs->add('v|verbose');
        $appspecs->add('c|color');
        $appspecs->add('d|debug');

        $cmdspecs = new OptionCollection;
        $cmdspecs->add('n|name:=string');
        $cmdspecs->add('p|phone:=string');
        $cmdspecs->add('a|address:=string');


        $subcommands = array('subcommand1','subcommand2','subcommand3');
        $subcommand_specs = array(
            'subcommand1' => $cmdspecs,
            'subcommand2' => $cmdspecs,
            'subcommand3' => $cmdspecs,
        );
        $subcommand_options = array();
        $arguments = array();

        $argv = explode(' ','program -v -d -c subcommand1 --name=c9s --phone=123123123 --address=somewhere arg1 arg2 arg3');
        $parser = new ContinuousOptionParser( $appspecs );
        $app_options = $parser->parse( $argv );
        while (! $parser->isEnd()) {
            if (@$subcommands[0] && $parser->getCurrentArgument() == $subcommands[0]) {
                $parser->advance();
                $subcommand = array_shift( $subcommands );
                $parser->setSpecs( $subcommand_specs[$subcommand] );
                $subcommand_options[ $subcommand ] = $parser->continueParse();
            } else {
                $arguments[] = $parser->advance();
            }
        }

        $this->assertCount(3, $arguments);
        $this->assertEquals('arg1', $arguments[0]);
        $this->assertEquals('arg2', $arguments[1]);
        $this->assertEquals('arg3', $arguments[2]);

        $this->assertNotNull($subcommand_options['subcommand1']);
        $this->assertEquals('c9s', $subcommand_options['subcommand1']->name );
        $this->assertEquals('123123123', $subcommand_options['subcommand1']->phone );
        $this->assertEquals('somewhere', $subcommand_options['subcommand1']->address );
    }


    /* test parser without options */
    function testParser4()
    {
        $appspecs = new OptionCollection;
        $appspecs->add('v|verbose');
        $appspecs->add('c|color');
        $appspecs->add('d|debug');

        $cmdspecs = new OptionCollection;
        $cmdspecs->add('a:'); // required
        $cmdspecs->add('b?'); // optional
        $cmdspecs->add('c+'); // multiple (required)



        $parser = new ContinuousOptionParser( $appspecs );
        ok( $parser );

        $subcommands = array('subcommand1','subcommand2','subcommand3');
        $subcommand_specs = array(
            'subcommand1' => clone $cmdspecs,
            'subcommand2' => clone $cmdspecs,
            'subcommand3' => clone $cmdspecs,
        );
        $subcommand_options = array();

        $argv = explode(' ','program subcommand1 subcommand2 subcommand3 -a a -b b -c c');
        $app_options = $parser->parse( $argv );
        $arguments = array();
        while( ! $parser->isEnd() ) {
            if( @$subcommands[0] && $parser->getCurrentArgument() == $subcommands[0] ) {
                $parser->advance();
                $subcommand = array_shift( $subcommands );
                $parser->setSpecs( $subcommand_specs[$subcommand] );
                $subcommand_options[ $subcommand ] = $parser->continueParse();
            } else {
                $arguments[] = $parser->advance();
            }
        }

        ok( $subcommand_options );
        ok( $subcommand_options['subcommand1'] );
        ok( $subcommand_options['subcommand2'] );
        ok( $subcommand_options['subcommand3'] );

        $r = $subcommand_options['subcommand3'];
        ok( $r );


        
        ok( $r->a , 'option a' );
        ok( $r->b , 'option b' );
        ok( $r->c , 'option c' );

        is( 'a', $r->a );
        is( 'b', $r->b );
        is( 'c', $r->c[0] );
    }

    /* test parser without options */
    function testParser5()
    {
        $appspecs = new OptionCollection;
        $appspecs->add('v|verbose');
        $appspecs->add('c|color');
        $appspecs->add('d|debug');

        $cmdspecs = new OptionCollection;
        $cmdspecs->add('a:');
        $cmdspecs->add('b');
        $cmdspecs->add('c');

        $parser = new ContinuousOptionParser( $appspecs );
        ok( $parser );

        $subcommands = array('subcommand1','subcommand2','subcommand3');
        $subcommand_specs = array(
            'subcommand1' => clone $cmdspecs,
            'subcommand2' => clone $cmdspecs,
            'subcommand3' => clone $cmdspecs,
        );
        $subcommand_options = array();

        $argv = explode(' ','program subcommand1 -a 1 subcommand2 -a 2 subcommand3 -a 3 arg1 arg2 arg3');
        $app_options = $parser->parse( $argv );
        $arguments = array();
        while( ! $parser->isEnd() ) {
            if( @$subcommands[0] && $parser->getCurrentArgument() == $subcommands[0] ) {
                $parser->advance();
                $subcommand = array_shift( $subcommands );
                $parser->setSpecs( $subcommand_specs[$subcommand] );
                $subcommand_options[ $subcommand ] = $parser->continueParse();
            } else {
                $arguments[] = $parser->advance();
            }
        }
        
        is( 'arg1', $arguments[0] );
        is( 'arg2', $arguments[1] );
        is( 'arg3', $arguments[2] );
        ok( $subcommand_options );

        is( 1, $subcommand_options['subcommand1']->a );
        ok( 2, $subcommand_options['subcommand2']->a );
        ok( 3, $subcommand_options['subcommand3']->a );
    }
}


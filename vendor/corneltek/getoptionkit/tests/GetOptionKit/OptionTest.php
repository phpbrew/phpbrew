<?php
use GetOptionKit\Option;

class OptionTest extends PHPUnit_Framework_TestCase
{


    public function optionSpecDataProvider() {
        return [
            ['i'],
            ['f'],
            ['a=number'],

            // long options
            ['n|name'],
            ['e|email'],
        ];
    }


    /**
     * @dataProvider optionSpecDataProvider
     */
    public function testOptionSpec($spec)
    {
        ok($spec);
        $opt = new Option($spec);
        ok($opt);
    }

    public function testDefaultValue() {
        $opt = new Option('z');
        $opt->defaultValue(10);
        $this->assertEquals(10, $opt->getValue());
    }

    public function testValidValues() {
        $opt = new Option('scope');
        $opt->validValues([ 'public', 'private' ])
            ;
        ok( $opt->getValidValues() );
        ok( is_array($opt->getValidValues()) );

        $opt->setValue('public');
        $opt->setValue('private');
        ok($opt->value);
        is('private',$opt->value);
    }


    public function testFilter() {
        $opt = new Option('scope');
        $opt->filter(function($val) { 
            return preg_replace('#a#', 'x', $val);
        })
        ;
        $opt->setValue('aa');
        is('xx', $opt->value);
    }
}


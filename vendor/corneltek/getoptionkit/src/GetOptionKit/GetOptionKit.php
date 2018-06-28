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
namespace GetOptionKit;
use GetOptionKit\Option;
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionResult;
use GetOptionKit\OptionParser;
use Exception;

class GetOptionKit 
{
    public $parser;
    public $specs;

    public function __construct($specs = null)
    {
        $this->specs = $specs ?: new OptionCollection;
        $this->parser = new OptionParser( $this->specs );
    }

    /* 
     * return current parser 
     * */
    public function getParser()
    {
        return $this->parser;
    }

    /* get all option specification */
    public function getSpecs()
    {
        return $this->specs;
    }

    /* a helper to build option specification object from string spec 
     *
     * @param $specString string
     * @param $description string
     * @param $key
     *
     * */
    public function add($specString, $description , $key = null ) 
    {
        $spec = $this->specs->add($specString,$description,$key);
        return $spec;
    }

    /* get option specification by Id */
    public function get($id)
    {
        return $this->specs->get($id);
    }

    public function parse(array $argv ) 
    {
        return $this->parser->parse( $argv );
    }

    public function printOptions( $class = 'GetOptionKit\OptionPrinter' )
    {
        $this->specs->printOptions( $class );
    }

}


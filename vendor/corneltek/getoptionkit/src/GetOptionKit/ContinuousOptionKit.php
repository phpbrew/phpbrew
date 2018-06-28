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
use GetOptionKit\ContinuousOptionParser;
use Exception;

/* A wrapper class for continuous option parser */
class ContinuousOptionKit extends GetOptionKit
{
    public function __construct()
    {
        $this->specs = new OptionCollection;
        $this->parser = new ContinuousOptionParser( $this->specs );
    }

    public function parse(array $argv ) 
    {
        return $this->parser->parse( $argv );
    }

    public function isEnd()
    {
        return $this->parser->isEnd();
    }

    public function continueParse()
    {
        return $this->parser->continueParse();
    }
}


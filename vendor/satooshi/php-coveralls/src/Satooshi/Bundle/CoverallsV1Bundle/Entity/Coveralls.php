<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Entity;

use Satooshi\Bundle\CoverallsBundle\Entity\ArrayConvertable;

/**
 * Data for Coveralls API.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
abstract class Coveralls implements ArrayConvertable
{
    /**
     * String expression (convert to json).
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}

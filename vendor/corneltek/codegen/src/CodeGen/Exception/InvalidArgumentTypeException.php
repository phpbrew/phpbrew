<?php
namespace CodeGen\Exception;

use InvalidArgumentException;

class InvalidArgumentTypeException extends InvalidArgumentException
{
    public $expectingTypes = array();

    public $givenType;

    public function __construct($message, $givenVariable, array $expectingTypes = array())
    {
        parent::__construct($message);
        if (is_object($givenVariable)) {
            $this->givenType = get_class($givenVariable);
        } else {
            $this->givenType = gettype($givenVariable);
        }
        $this->expectingTypes = $expectingTypes;
    }
}




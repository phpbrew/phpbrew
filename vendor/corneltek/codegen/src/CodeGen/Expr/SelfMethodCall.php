<?php
namespace CodeGen\Expr;

/**
 * This is a shorthand class for generating $this->foo( ... );
 */
class SelfMethodCall extends MethodCall
{
    public function __construct($method = NULL, array $arguments = array())
    {
        parent::__construct('$this', $method, $arguments);
    }
}


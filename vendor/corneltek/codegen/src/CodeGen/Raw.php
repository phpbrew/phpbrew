<?php
namespace CodeGen;

class Raw
{
    /**
     * @var string
     */
    protected $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function __toString()
    {
        return (string)$this->code;
    }
}





<?php
namespace CodeGen\Statement;

use ReflectionClass;

class RequireClassStatement extends RequireStatement
{
    public function __construct($class)
    {
        $refl = new ReflectionClass($class);
        $file = $refl->getFileName();
        $this->expr = $file;
    }
}




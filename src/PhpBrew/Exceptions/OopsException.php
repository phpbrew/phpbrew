<?php
namespace PhpBrew\Exceptions;
use Exception;

class OopsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Oops, report this issue on GitHub? http://github.com/c9s/phpbrew ');
    }
}




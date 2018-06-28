<?php
namespace CodeGen\Generator;

use CodeGen\UserClass;


/**
 * Unfinished
 */
class ArrayAccessGenerator
{
    public function __construct()
    {

    }

    /*
    abstract public boolean offsetExists ( mixed $offset )
    abstract public mixed offsetGet ( mixed $offset )
    abstract public void offsetSet ( mixed $offset , mixed $value )
    abstract public void offsetUnset ( mixed $offset )
    */
    public function generate($arrayPropertyName, UserClass $class)
    {
        $class->implementInterface('ArrayAccess');

        // $class->addProtectedProperty
        $class->addMethod('public', 'offsetSet', array('$key', '$val'), array(
            "\$this->{$arrayPropertyName}[\$key] = \$val;"
        ));
        $class->addMethod('public', 'offsetGet', array('$key'), array(
            "return \$this->{$arrayPropertyName}[\$key];"
        ));
        $class->addMethod('public', 'offsetExists', array('$key'), array(
            "return isset(\$this->{$arrayPropertyName}[\$key]);",
        ));
        $class->addMethod('public', 'offsetUnset', array('$key'), array(
            "unsetset(\$this->{$arrayPropertyName}[\$key]);",
        ));
        return $class;
    }
}





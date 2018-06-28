<?php
namespace CodeGen;

use LogicException;

class VariableDeflator
{
    static public function deflate($arg)
    {
        // Raw string output
        if (is_string($arg)) {
            if ($arg[0] === '$') {
                return $arg;
            }
            return var_export($arg, true);
        } else if ($arg instanceof Renderable) {
            return $arg->render(array());
        } else if ($arg instanceof Raw) {
            return (string)$arg;
        } else if ($arg instanceof Exportable || method_exists($arg, '__get_state')) {
            $class = get_class($arg);
            return $class . '::__set_state(' . var_export($arg->__get_state(), true) . ')';
        } else if (is_array($arg) || is_scalar($arg) || method_exists($arg, '__set_state')) {
            return var_export($arg, true);
        } else if ($arg === null) {
            return '';
        } else {
            throw new LogicException('Can\'t deflate variable');
        }
    }
}





<?php
namespace CodeGen;

class Indenter
{
    static public $expandTab = true;

    static public $spaceWidth = 4;

    /**
     * @param int $level
     * @return string
     */
    static public function indent($level = 1)
    {
        if (self::$expandTab) {
            $tab = str_repeat(' ', self::$spaceWidth);
            return str_repeat($tab, $level);
        } else {
            return str_repeat("\t", $level);
        }

    }
}





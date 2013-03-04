<?php
namespace PhpBrew;
use Exception;

class DirectorySwitch
{
    protected $stack = array();

    protected function _chdir($dir)
    {
        if( false === chdir($dir) ) {
            throw new Exception("Can not change directory to $dir.");
        }
        return $dir;
    }

    public function cd($dir)
    {
        if(empty($this->stack)) {
            $this->stack[] = getcwd();
        }
        $this->stack[] = $this->_chdir($dir);
    }

    public function back()
    {
        if ( !empty($this->stack) ) {
            $dir = array_pop($this->stack);
            $this->_chdir($dir);
        } else {
            throw new Exception("The directory stack is empty. Can not go back.");
        }
    }

}



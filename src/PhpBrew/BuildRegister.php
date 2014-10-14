<?php
namespace PhpBrew;
use PhpBrew\Build;
use PhpBrew\Config;

class BuildRegister
{
    public $root;

    public $baseDir;

    public function __construct() {
        $this->root = Config::getPhpbrewRoot();
        $this->baseDir = $this->root . DIRECTORY_SEPARATOR . 'register';
        if (!file_exists($this->baseDir)) {
            mkdir($this->baseDir, 0755, true);
        }
    }

    public function register(Build $build) {
        $file = $this->baseDir . DIRECTORY_SEPARATOR . $build->getName();
        $build->writeFile($file);
    }

    public function deregister(Build $build) {
        $file = $this->baseDir . DIRECTORY_SEPARATOR . $build->getName();
        if (file_exists($file)) {
            unlink($file);
            return true;
        }
        return false;
    }
}



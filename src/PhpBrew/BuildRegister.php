<?php

namespace PhpBrew;

class BuildRegister
{
    public $root;

    public $baseDir;

    public function __construct()
    {
        $this->root = Config::getRoot();
        $this->baseDir = $this->root . DIRECTORY_SEPARATOR . 'registry';
    }

    public function register(Build $build)
    {
        $file = $this->baseDir . DIRECTORY_SEPARATOR . $build->getName();

        return $build->writeFile($file);
    }

    public function deregister(Build $build)
    {
        $file = $this->baseDir . DIRECTORY_SEPARATOR . $build->getName();
        if (file_exists($file)) {
            unlink($file);

            return true;
        }

        return false;
    }
}

<?php
namespace PhpBrew;

class ExtensionMetaM4 extends ExtensionMetaAbstraction implements ExtensionMetaInterface
{
    protected $m4;
    protected $name;
    protected $is_zend;

    public function __construct($m4)
    {
        if (!file_exists($m4)) {
            throw new \Exception("Error loading m4 file: {$m4}");
        }

        $this->m4 = file_get_contents($m4);
        preg_match_all('#(?<=PHP_NEW_EXTENSION\()\w+#s', $this->m4, $matches);
        $this->name = $matches[0][0];
        $this->is_zend = (1 === preg_match('/PHP_NEW_EXTENSION\(([^,]*,){6}\s*yes/', $this->m4));
    }

    public function isZend()
    {
        return $this->is_zend;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRuntimeName()
    {
        return $this->getName();
    }

    public function getVersion()
    {
        return null;
    }
}

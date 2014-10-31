<?php
namespace PhpBrew;
use CLIFramework\Logger;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\ExtensionMetaInterface;

class Extension
{

    /**
     * Extension meta
     * @var \PhpBrew\ExtensionMetaInterface
     */
    protected $meta;

    public function __construct($name, ExtensionMetaInterface $meta)
    {
        Migrations::setupConfigFolder();
        $this->meta = $meta;
    }

    public function purge()
    {
        $ini = $this->meta->getIniFile();
        unlink($ini);
        unlink($ini . '.disabled');
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function setMeta($meta) {
        $this->meta = $meta;
    }
}

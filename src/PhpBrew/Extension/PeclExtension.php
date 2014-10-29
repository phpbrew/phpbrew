<?php
namespace PhpBrew\Extension;
use PEARX\Package;

class PeclExtension extends Extension
{
    public $package;

    public function setPackage(Package $pkg) {
        $this->package = $pkg;
        $this->setVersion($pkg->getReleaseVersion() );

        if ($pkg->getZendExtSrcRelease()) {
            $this->setZend(true);
        }

        if ($n = $pkg->getProvidesExtension()) {
            $this->setExtensionName($n);
            $this->setSharedLibraryName($n . '.so');
        }
    }

    public function getPackage()
    {
        return $this->package;
    }
}




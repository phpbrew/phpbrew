<?php

namespace PhpBrew\Extension;

use PEARX\Package;

class PeclExtension extends Extension
{
    public $package;

    public function setPackage(Package $pkg)
    {
        $this->package = $pkg;
        $this->setVersion($pkg->getReleaseVersion());

        if ($pkg->getZendExtSrcRelease()) {
            $this->setZend(true);
        }

        if ($n = strtolower($pkg->getProvidesExtension())) {
            $this->setExtensionName($n);
            $this->setSharedLibraryName($n.'.so');
        }

        if ($options = $pkg->getConfigureOptions()) {
            $this->configureOptions = array();
            foreach ($options as $option) {
                $this->addConfigureOption(new ConfigureOption('--'.$option->name, $option->prompt, $option->default));
            }
        }
    }

    public function getPackage()
    {
        return $this->package;
    }

    public function findConfigM4FileFromPackageXml()
    {
        if ($contents = $this->package->getContents()) {
            foreach ($contents as $content) {
                if (preg_match('#config[0-9]*.m4$#', $content->file)) {
                    // TODO: make sure the file exists
                    return $content->file;
                }
            }
        }
    }

    public function findConfigM4File($dir)
    {
        if ($file = parent::findConfigM4File($dir)) {
            return $file;
        }
        if ($file = $this->findConfigM4FileFromPackageXml()) {
            return $file;
        }
    }
}

<?php
namespace PhpBrew\Extension;
use PhpBrew\Config;
use PhpBrew\Extension;
use PhpBrew\ExtensionMetaM4;
use PhpBrew\ExtensionMetaXml;

/**
 * This factory class handles the extension information
 */
class ExtensionFactory
{
    static public function create($packageName) {
        $extensionDir = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext/'. $packageName;

        $packageXmlPath = $extensionDir . '/package.xml';

        // If the package.xml exists, we may get the configureoptions for configuring the Makefile 
        // and use the provided extension name to enable the extension.
        //
        // Currently only PECL extensions have package.xml, however It's the 
        // best strategy to install the extension.
        if (file_exists($packageXmlPath)) {
            // $this->logger->warning("===> Using xml extension meta");
            return self::createPeclExtension($packageName, $packageXmlPath);
        }

        // If the config.m4 or config0.m4 exists, it requires us to run `phpize` to 
        // initialize the `configure` script.
        //
        // It's basically a fallback for extensions that don't have package.xml.
        // Generlly, The possible extensions using this strategy are usually 
        // PHP's core extensions, which are shipped in the distribution file.
        $configM4Path = $extensionDir . '/config.m4';
        if (file_exists($configM4Path)) {
            // $this->logger->warning("===> Using m4 extension meta");
            return self::createM4Extension($packageName, $configM4Path);
        }

        // quote:
        //   the 0 there makes sure it gets into another stage of the buildprocess, the
        //   top IIRC, it was added @ 12th May 2001, 12:09am (10 months ago).
        //
        // http://grokbase.com/t/php/php-dev/023cpdc9k6/quick-summary-of-changes
        //
        // When config[0-9].m4 found, it might be an extension that can't be 
        // installed as a shared extension. We will need to raise a warning 
        // message for users.
        for ($i = 0 ; $i < 10 ; $i++ ) {
            $configM4Path = $extensionDir . "/config{$i}.m4";
            if (file_exists($configM4Path)) {
                // $this->logger->warning("===> Using m4 extension meta");
                return self::createM4Extension($packageName, $configM4Path);
            }
        }

        // $this->logger->warning("===> Using polyfill extension meta");
        $meta = new ExtensionMetaPolyfill($packageName);
        return new Extension($packageName, $meta);
    }

    static public function createM4Extension($packageName, $m4Path) {
        $meta = new ExtensionMetaM4($m4Path);
        return new Extension($packageName, $meta);
    }

    static public function createPeclExtension($packageName, $packageXmlPath) {
        $meta = new ExtensionMetaXml($packageXmlPath);
        return new Extension($packageName, $meta);
    }

}




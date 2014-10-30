<?php
namespace PhpBrew\Extension;
use PhpBrew\Config;
use PhpBrew\Extension as Extension1;
use PhpBrew\ExtensionMetaM4;
use PhpBrew\ExtensionMetaXml;
use PhpBrew\Extension\PeclExtension;
use PEARX\PackageXml\Parser as PackageXmlParser;

/**
 * This factory class handles the extension information
 */
class ExtensionFactory
{
    static public function createFromDirectory($packageName, $extensionDir) {
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
                return self::createM4Extension($packageName, $configM4Path);
            }
        }
    }

    static public function lookupRecursive($packageName, array $lookupDirs = array() ) {
        foreach($lookupDirs as $lookupDir) {
            /**
            * FOLLOW_SYMLINKS is available from 5.2.11, 5.3.1
            */
            $di = new RecursiveDirectoryIterator($lookupDir, 
                RecursiveDirectoryIterator::FOLLOW_SYMLINKS | 
                RecursiveDirectoryIterator::SKIP_DOTS 
            );
            $it = new RecursiveIteratorIterator($di);

            /**
            * Search for config.m4 or config0.m4 and use them to determine
            * the directory of the extension's source, because it's not always
            * the root directory in the ext archive (example xhprof)
            */
            foreach ($it as $fileinfo) {
                if (!$fileinfo->isDir()) {
                    continue;
                }
                if ($ext = $this->createFromDirectory($packageName, $fileinfo->__toString())) {
                    return $ext;
                }
            }
        }
    }

    static public function lookup($packageName, $lookupDirectories = array(), $fallback = true) 
    {

        if ($fallback) {
            // Always push the PHP source directory to the end of the list for the fallback.
            $lookupDirectories[] = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';
        }

        foreach($lookupDirectories as $lookupDir) {
            $extensionDir = $lookupDir . DIRECTORY_SEPARATOR . $packageName;
            if ($ext = self::createFromDirectory($packageName, $extensionDir)) {
                return $ext;
            }
        }
    }

    static public function createM4Extension($packageName, $m4Path) {
        // $meta = new ExtensionMetaM4($m4Path);
        $m4 = file_get_contents($m4Path);

        // PHP_NEW_EXTENSION(extname, sources [, shared [, sapi_class [, extra-cflags [, cxx [, zend_ext]]]]])
        if (preg_match('/PHP_NEW_EXTENSION\( \s* 
                ([^,]+)   # The extension name
                \s*,\s* ([^,]*)  # Source files
                \s*,\s* ([^,)]*)  # Ext Shared

                (?:
                    \s*,\s* ([^,)]*)  # SAPI class
                    \s*,\s* ([^,)]*)  # Extra cflags
                    \s*,\s* ([^,)]*)  # CXX
                    \s*,\s* ([^,)]*)  # zend extension
                )?
                /x', $m4, $matches)) {

            $ext = new M4Extension($packageName);
            $ext->setExtensionName($matches[0]);
            $ext->setSharedLibraryName($matches[0] . '.so');
            if (isset($matches[6]) && strpos($matches[6], 'yes') !== false) {
                $ext->setZend(true);
            }
            $ext->setSourceDirectory(dirname($m4Path));
            return $ext;

        } else {
            throw new Exception("Can not parse config m4 $m4Path");
        }
    }

    static public function createPeclExtension($packageName, $packageXmlPath) {
        $parser = new PackageXmlParser;
        $package = $parser->parse($packageXmlPath);
        $ext = new PeclExtension($packageName);
        $ext->setPackage($package);
        $ext->setSourceDirectory(dirname($packageXmlPath));
        return $ext;
        // $meta = new ExtensionMetaXml($packageXmlPath);
        // return new Extension1($packageName, $meta);
    }

}




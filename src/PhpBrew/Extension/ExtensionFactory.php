<?php
namespace PhpBrew\Extension;
use PhpBrew\Config;
use PhpBrew\Extension\PeclExtension;
use PhpBrew\Extension\M4Extension;
use PhpBrew\Extension\ConfigureOption;
use PEARX\PackageXml\Parser as PackageXmlParser;
use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * This factory class handles the extension information
 */
class ExtensionFactory
{

    /**
     * One extension directory might contains multiple config*.m4 file, like 
     * memcache extension.
     */
    static public function configM4Exists($extensionDir) 
    {
        $files = array();
        $configM4Path = $extensionDir . DIRECTORY_SEPARATOR . 'config.m4';
        if (file_exists($configM4Path)) {
            $files[] = $configM4Path;
        }
        for ($i = 0 ; $i < 10 ; $i++ ) {
            $configM4Path = $extensionDir . DIRECTORY_SEPARATOR . "config{$i}.m4";
            if (file_exists($configM4Path)) {
                $files[] = $configM4Path;
            }
        }
        return $files;
    }

    static public function createFromDirectory($packageName, $extensionDir) {
        $packageXmlPath = $extensionDir . DIRECTORY_SEPARATOR . 'package.xml';

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
        // quote:
        //   the 0 there makes sure it gets into another stage of the buildprocess, the
        //   top IIRC, it was added @ 12th May 2001, 12:09am (10 months ago).
        //
        // http://grokbase.com/t/php/php-dev/023cpdc9k6/quick-summary-of-changes
        //
        // When config[0-9].m4 found, it might be an extension that can't be 
        // installed as a shared extension. We will need to raise a warning 
        // message for users.
        $configM4Paths = self::configM4Exists($extensionDir);
        foreach($configM4Paths as $m4path) {
            if (file_exists($m4path)) {
                try {
                    return self::createM4Extension($packageName, $m4path);
                } catch(Exception $e) {
                    // Can't parse the content, ignore the error and continue the parsing...
                }
            }
        }
    }

    static public function lookupRecursive($packageName, array $lookupDirs = array(), $fallback = true) {
        if ($fallback) {
            // Always push the PHP source directory to the end of the list for the fallback.
            $lookupDirs[] = Config::getBuildDir() . DIRECTORY_SEPARATOR . Config::getCurrentPhpName() . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . $packageName;
        }

        foreach($lookupDirs as $lookupDir) {
            if (!file_exists($lookupDir)) {
                continue;
            }

            if ($ext = self::createFromDirectory($packageName, $lookupDir)) {
                return $ext;
            }

            /**
            * FOLLOW_SYMLINKS is available from 5.2.11, 5.3.1
            */
            $di = new RecursiveDirectoryIterator($lookupDir, RecursiveDirectoryIterator::SKIP_DOTS);
            $it = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);

            /**
            * Search for config.m4 or config0.m4 and use them to determine
            * the directory of the extension's source, because it's not always
            * the root directory in the ext archive (example xhprof)
            */
            foreach ($it as $fileinfo) {
                if (!$fileinfo->isDir()) {
                    continue;
                }
                if ($ext = self::createFromDirectory($packageName, $fileinfo->getPathName())) {
                    return $ext;
                }
            }
        }
    }

    static public function lookup($packageName, array $lookupDirectories = array(), $fallback = true) 
    {

        if ($fallback) {
            // Always push the PHP source directory to the end of the list for the fallback.
            $lookupDirectories[] = Config::getBuildDir() . '/' . Config::getCurrentPhpName() . '/ext';
        }

        foreach($lookupDirectories as $lookupDir) {
            if (!file_exists($lookupDir)) {
                continue;
            }

            $extensionDir = $lookupDir . DIRECTORY_SEPARATOR . $packageName;
            if ($ext = self::createFromDirectory($packageName, $extensionDir)) {
                return $ext;
            }
        }
    }

    static public function createM4Extension($packageName, $m4Path) {
        if (!file_exists($m4Path)) {
            return NULL;
        }

        $m4 = file_get_contents($m4Path);

        // PHP_NEW_EXTENSION(extname, sources [, shared [, sapi_class [, extra-cflags [, cxx [, zend_ext]]]]])
        if (preg_match('/PHP_NEW_EXTENSION \( \s* 
                \[?
                    (\w+)   # The extension name
                \]?

                \s*,\s*

                \[?
                    ([^,]*)  # Source files
                \]?

                (?:
                    \s*,\s* ([^,\)]*)  # Ext Shared

                    (?:
                        \s*,\s* ([^,\)]*)  # SAPI class

                        (?:
                            \s*,\s* ([^,\)]*)  # Extra cflags

                            (?:
                                \s*,\s* ([^,\)]*)  # CXX
                                \s*,\s* ([^,\)]*)  # zend extension
                            )?
                        )?
                    )?
                )?
                /x', $m4, $matches)) {
                    
            $fullmatched = array_shift($matches);
            $ext = new M4Extension($packageName);
            $ext->setExtensionName($matches[0]);
            $ext->setSharedLibraryName($matches[0] . '.so');
            if (isset($matches[6]) && strpos($matches[6], 'yes') !== false) {
                $ext->setZend(true);
            }
            $ext->setSourceDirectory(dirname($m4Path));


            /*
            PHP_ARG_ENABLE(calendar,whether to enable calendar conversion support,
            [  --enable-calendar       Enable support for calendar conversion])
            */
            if (preg_match('/
                PHP_ARG_ENABLE\(
                    \s*([^,]*)
                    (?:
                        \s*,\s*
                        (
                            [^,\)]*
                        )
                        (?:
                            \s*,\s*
                            \[ 
                                \s* 
                                ([^\s]+)
                                \s+ 
                                ([^,\)]*)
                                \s* 
                            \]
                        )?
                    )?/x', $m4, $matches)) {

                // shift the first match
                array_shift($matches);
                $name = array_shift($matches);
                $desc = array_shift($matches);
                $option = array_shift($matches);
                $optionDesc = array_shift($matches);
                $ext->addConfigureOption(new ConfigureOption($option ?: '--enable-' . $name, $desc ?: $optionDesc));
            }

            /*
            PHP_ARG_WITH(gd, for GD support,
            [  --with-gd[=DIR]   Include GD support.  DIR is the GD library base
                                    install directory [BUNDLED]])


            Possible option formats:

                --with-libxml-dir=DIR
                --with-recode[=DIR]
                --with-yaml[[=DIR]]
                --with-mysql-sock[=SOCKPATH]
            */
            if (preg_match('/
                PHP_ARG_WITH\(
                    \s*([^,]*)
                    (?:
                        \s*,\s*([^,\)]*)
                        (?:
                            \s*,\s* 

                            \[ 
                                \s*

                                # simple match (\S+)

                                ([a-zA-Z0-9-]+)  # option
                                (?:
                                    =?

                                    \[?
                                        =?([^\s\]]*?) 
                                    \]?
                                )?                 # option value hint

                                \s+

                                ([^,\)]*)        # option description
                                \s*                 
                            \]
                        )?
                    )?/x', $m4, $matches)) {

                // shift the first match
                array_shift($matches);
                $name = array_shift($matches);
                $desc = array_shift($matches);
                $option = array_shift($matches);
                $optionValueHint = array_shift($matches);
                $optionDesc      = array_shift($matches);
                $ext->addConfigureOption(new ConfigureOption(( $option ?: '--with-' . $name), ($desc ?: $optionDesc), $optionValueHint));
            }

            return $ext;
        } else {
            throw new Exception("Can not parse config.m4: $m4Path");
        }
    }

    static public function createPeclExtension($packageName, $packageXmlPath) {
        $parser = new PackageXmlParser;
        $package = $parser->parse($packageXmlPath);
        $ext = new PeclExtension($packageName);
        $ext->setPackage($package);

        /**
         * xhprof stores package.xml in the root directory, but putting the 
         * config.m4 in the extension directory.
         * the path can be retrieve from the contents part from the package.xml
         */
        if ($m4path = $ext->findConfigM4FileFromPackageXml()) {
            $ext->setSourceDirectory(dirname($packageXmlPath) . DIRECTORY_SEPARATOR . dirname($m4path));
        } else {
            $ext->setSourceDirectory(dirname($packageXmlPath));
        }
        return $ext;
    }

}




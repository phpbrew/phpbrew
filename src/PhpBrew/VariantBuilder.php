<?php
namespace PhpBrew;

use Exception;
use RuntimeException;
use PhpBrew\Exception\OopsException;
use PhpBrew\Build;

/**
 * VariantBuilder build variants to configure options.
 *
 * TODO: In future, we want different kind of variant:
 *
 *    1. configure option variant
 *    2. pecl package variant, e.g. +xdebug +phpunit
 *    3. config settings variant.  +timezone=Asia/Taipei
 *
 * API:
 *
 * $variantBuilder = new VariantBuilder;
 * $variantBuilder->register('debug', function () {
 *
 * });
 * $variantBuilder->build($build);
 */
class VariantBuilder
{

    /**
     * available variants
     */
    public $variants = array();

    public $conflicts = array(
        // PHP Version lower than 5.4.0 can only built one SAPI at the same time.
        'apxs2' => array('fpm','cgi'),
        'editline' => array('readline'),
        'readline' => array('editline'),
    );

    public $options = array();

    /**
     * @var array $builtList is for checking built variants
     *
     * contains ['-pdo','mysql','-sqlite','-debug']
     */
    public $builtList = array();

    public $virtualVariants = array(
        'dbs' => array(
            'sqlite',
            'mysql',
            'pgsql',
            'pdo'
        ),

        'mb' => array(
            'mbstring',
            'mbregex',
        ),

        // provide no additional feature
        'neutral' => array(),

        'small' => array(
            'bz2',
            'cli',
            'dom',
            'filter',
            'ipc',
            'json',
            'mbregex',
            'mbstring',
            'pcre',
            'phar',
            'posix',
            'readline',
            'xml',
            'curl',
            'openssl',
        ),

        // provide all basic features
        'default' => array(
            'bcmath',
            'bz2',
            'calendar',
            'cli',
            'ctype',
            'dom',
            'fileinfo',
            'filter',
            'ipc',
            'json',
            'mbregex',
            'mbstring',
            'mhash',
            'mcrypt',
            'pcntl',
            'pcre',
            'pdo',
            'phar',
            'posix',
            'readline',
            'sockets',
            'tokenizer',
            'xml',
            'curl',
            'openssl',
            'zip',
        ),
    );

    public function __construct()
    {
        // init variant builders
        $this->variants['all']      = '--enable-all';
        $this->variants['dba']      = '--enable-dba';
        $this->variants['ipv6']     = '--enable-ipv6';
        $this->variants['dom']      = '--enable-dom';
        $this->variants['calendar'] = '--enable-calendar';
        $this->variants['wddx']     = '--enable-wddx';
        $this->variants['static']   = '--enable-static';
        $this->variants['inifile']  = '--enable-inifile';
        $this->variants['inline']   = '--enable-inline-optimization';

        $this->variants['cli']      = '--enable-cli';
        $this->variants['fpm']      = '--enable-fpm';
        $this->variants['ftp']      = '--enable-ftp';
        $this->variants['filter']   = '--enable-filter';
        $this->variants['gcov']     = '--enable-gcov';
        $this->variants['zts']      = '--enable-maintainer-zts';

        $this->variants['json']     = '--enable-json';
        $this->variants['hash']     = '--enable-hash';
        $this->variants['exif']     = '--enable-exif';
        $this->variants['mbstring'] = '--enable-mbstring';
        $this->variants['mbregex']  = '--enable-mbregex';
        $this->variants['libgcc']   = '--enable-libgcc';
        // $this->variants['gd-jis'] = '--enable-gd-jis-conv';

        $this->variants['pdo']      = '--enable-pdo';
        $this->variants['posix']    = '--enable-posix';
        $this->variants['embed']    = '--enable-embed';
        $this->variants['sockets']  = '--enable-sockets';
        $this->variants['debug']    = '--enable-debug';
        $this->variants['phpdbg']    = '--enable-phpdbg';

        $this->variants['zip']      = '--enable-zip';
        $this->variants['bcmath']   = '--enable-bcmath';
        $this->variants['fileinfo'] = '--enable-fileinfo';
        $this->variants['ctype']    = '--enable-ctype';
        $this->variants['cgi']      = '--enable-cgi';
        $this->variants['soap']     = '--enable-soap';
        $this->variants['gcov']     = '--enable-gcov';
        $this->variants['pcntl']    = '--enable-pcntl';


        /*
        --enable-intl

         To build the extension you need to install the » ICU library, version
         4.0.0 or newer is required.
         This extension is bundled with PHP as of PHP version 5.3.0.
         Alternatively, the PECL version of this extension may be used with all
         PHP versions greater than 5.2.0 (5.2.4+ recommended).

         This requires --with-icu-dir=/....
         */
        $this->variants['intl']     = '--enable-intl';

        $this->variants['phar']     = '--enable-phar';
        $this->variants['session']     = '--enable-session';
        $this->variants['tokenizer']     = '--enable-tokenizer';

        // opcache was added since 5.6
        $this->variants['opcache']     = '--enable-opcache';

        $this->variants['imap'] = '--with-imap-ssl';
        $this->variants['tidy'] = '--with-tidy';
        $this->variants['kerberos'] = '--with-kerberos';
        $this->variants['xmlrpc'] = '--with-xmlrpc';
        $this->variants['pcre'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return array("--with-pcre-regex", "--with-pcre-dir=$prefix");
            }

            if ($prefix = Utils::findIncludePrefix('pcre.h')) {
                return array("--with-pcre-regex", "--with-pcre-dir=$prefix");
            }

            return array("--with-pcre-regex");
        };

        $this->variants['mhash'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-mhash=$prefix";
            }

            if ($prefix = Utils::findIncludePrefix('mhash.h')) {
                return "--with-mhash=$prefix";
            }

            if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix mhash", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    return '--with-mhash=' . end(array_filter($output));
                }
            }

            return "--with-mhash"; // let autotool to find it.
        };

        $this->variants['mcrypt'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-mcrypt=$prefix";
            }

            if ($prefix = Utils::findIncludePrefix('mcrypt.h')) {
                return "--with-mcrypt=$prefix";
            }

            if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix mcrypt", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    return '--with-mcrypt=' . end(array_filter($output));
                }
            }

            return "--with-mcrypt"; // let autotool to find it.
        };

        $this->variants['zlib'] = function (Build $build) {
            if ($prefix = Utils::findIncludePrefix('zlib.h')) {
                return '--with-zlib=' . $prefix;
            }

            return null;
        };

        $this->variants['curl'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-curl=$prefix";
            }

            if ($prefix = Utils::findIncludePrefix('curl/curl.h')) {
                return "--with-curl=$prefix";
            }

            if ($prefix = Utils::getPkgConfigPrefix('libcurl')) {
                return "--with-curl=$prefix";
            }

            if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix curl", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    return '--with-curl=' . end(array_filter($output));
                }
            }
            return null;
        };

        /*
        Users might prefer readline over libedit because only readline supports
        readline_list_history() (http://www.php.net/readline-list-history).
        On the other hand we want libedit to be the default because its license
        is compatible with PHP's which means PHP can be distributable.
        
        related issue https://github.com/phpbrew/phpbrew/issues/497
        */
        $this->variants['readline'] = function (Build $build, $prefix = null) {
            if ($prefix = Utils::findIncludePrefix('readline' . DIRECTORY_SEPARATOR . 'readline.h')) {
                return '--with-readline=' . $prefix;
            } else if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix readline", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    return '--with-readline=' . end(array_filter($output));
                }
            }
            return '--with-readline';
        };


        /*
         * editline is conflict with readline
         */
        $this->variants['editline'] = function (Build $build, $prefix = null) {
            if ($prefix = Utils::findIncludePrefix('editline' . DIRECTORY_SEPARATOR . 'readline.h')) {
                return '--with-libedit=' . $prefix;
            }
        };



        $this->variants['gd'] = function (Build $build, $prefix = null) {
            $opts = array();

            // it looks like gd won't be compiled without "shared"
            // suggested options is +gd=shared,/usr

            if ($prefix) {
                $opts[] = "--with-gd=$prefix";
            } elseif ($prefix = Utils::findIncludePrefix('gd.h')) {
                $opts[] = "--with-gd=shared,$prefix";
            }

            $opts[] = '--enable-gd-native-ttf';

            if ($prefix = Utils::findIncludePrefix('jpeglib.h')) {
                $opts[] = "--with-jpeg-dir=$prefix";
            } else if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix libjpeg", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    $opts[] = '--with-jpeg-dir=' . end(array_filter($output));
                }
            }

            if ($prefix = Utils::findIncludePrefix('png.h', 'libpng12/pngconf.h')) {
                $opts[] = "--with-png-dir=$prefix";
            } else if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix libpng", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    $opts[] = '--with-png-dir=' . end(array_filter($output));
                }
            }

            // the freetype-dir option does not take prefix as its value,
            // it takes the freetype.h directory as its value.
            //
            // from configure:
            //   for path in $i/include/freetype2/freetype/freetype.h
            if ($prefix = Utils::findIncludePrefix('freetype2/freetype.h')) {
                $opts[] = "--with-freetype-dir=$prefix";
            } else if ($prefix = Utils::findIncludePrefix("freetype2/freetype/freetype.h")) {
                $opts[] = "--with-freetype-dir=$prefix";
            } else if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix freetype", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    $opts[] = '--with-freetype-dir=' . end(array_filter($output));
                }
            }

            return $opts;
        };


        /**
         * with icu
         */
        $this->variants['icu'] = function (Build $build, $val = null) {
            if ($val) {
                return '--with-icu-dir=' . $val;
            }

            // For homebrew
            if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix icu4c", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    return '--with-icu-dir=' . end(array_filter($output[0]));
                }
            }

            // For macports
            if ($prefix = Utils::getPkgConfigPrefix('icu-i18n')) {
                return '--with-icu-dir=' . $prefix;
            }

            // the last one path is for Ubuntu
            if ($prefix = Utils::findLibPrefix('icu/pkgdata.inc', 'icu/Makefile.inc')) {
                return '--with-icu-dir=' . $prefix;
            }

            throw new RuntimeException(
                "libicu not found, please install libicu-dev or libicu library/development files."
            );
        };


        /**
         * --with-openssl option
         *
         * --with-openssh=shared
         * --with-openssl=[dir]
         *
         * On ubuntu you need to install libssl-dev
         */
        $this->variants['openssl'] = function (Build $build, $val = null) {
            if ($val) {
                return "--with-openssl=$val";
            }

            if ($prefix = Utils::findIncludePrefix('openssl/opensslv.h')) {
                return "--with-openssl=$prefix";
            }

            if ($prefix = Utils::getPkgConfigPrefix('openssl')) {
                return "--with-openssl=$prefix";
            }


            // Special detection and fallback for homebrew openssl
            // @see https://github.com/phpbrew/phpbrew/issues/607
            if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix openssl", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    return '--with-openssl=' . end(array_filter($output));
                }
            }
            $possiblePrefixes = array('/usr/local/opt/openssl');
            $foundPrefixes = array_filter($possiblePrefixes, "file_exists");
            if (count($foundPrefixes) > 0) {
                return "--with-openssl=" . $foundPrefixes[0];
            }

            // This will create openssl.so file for dynamic loading.
            echo "Compiling with openssl=shared, please install libssl-dev or openssl header files if you need";
            return "--with-openssl";
        };

        /*
        --with-mysql[=DIR]      Include MySQL support.  DIR is the MySQL base
                                directory.  If mysqlnd is passed as DIR,
                                the MySQL native driver will be used [/usr/local]

        --with-mysqli[=FILE]    Include MySQLi support.  FILE is the path
                                to mysql_config.  If mysqlnd is passed as FILE,
                                the MySQL native driver will be used [mysql_config]

        --with-pdo-mysql[=DIR]    PDO: MySQL support. DIR is the MySQL base directoy
                                If mysqlnd is passed as DIR, the MySQL native
                                native driver will be used [/usr/local]

        --with-mysql            deprecated in 7.0

        mysqlnd was added since php 5.3
        */
        $this->variants['mysql'] = function (Build $build, $prefix = 'mysqlnd') {

            $opts = array();
            if ($build->compareVersion('7.0') < 0) {
                $opts[] = "--with-mysql=$prefix";
            }

            $opts[] = "--with-mysqli=$prefix";
            if ($build->hasVariant('pdo')) {
                $opts[] = "--with-pdo-mysql=$prefix";
            }

            $foundSock = false;
            if ($bin = Utils::findBin('mysql_config')) {
                exec("$bin --socket", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    $foundSock = true;
                    $opts[] = "--with-mysql-sock=" . end(array_filter($output));
                }
            }
            if (!$foundSock) {
                $possiblePaths = array(
                    /* macports mysql ... */
                    '/opt/local/var/run/mysql57/mysqld.sock',
                    '/opt/local/var/run/mysql56/mysqld.sock',
                    '/opt/local/var/run/mysql55/mysqld.sock',
                    '/opt/local/var/run/mysql54/mysqld.sock',

                    '/tmp/mysql.sock', /* homebrew mysql sock */
                    '/var/run/mysqld/mysql.sock',
                    '/var/mysql/mysql.sock',
                );
                $paths = array_filter($possiblePaths, "file_exists");
                if (count($paths) > 0 && file_exists($paths[0])) {
                    $opts[] = "--with-mysql-sock={$paths[0]}";
                }
            }
            return $opts;
        };


        $this->variants['sqlite'] = function (Build $build, $prefix = null) {
            $opts = array(
                '--with-sqlite3' . ($prefix ? "=$prefix" : '')
            );

            if ($build->hasVariant('pdo')) {
                $opts[] = '--with-pdo-sqlite';
            }

            return $opts;
        };

        $this->variants['pgsql'] = function (Build $build, $prefix = null) {
            $opts = array();

            // The names are used from macports
            $possibleNames = array('psql90','psql91','psql92','psql93','psql');
            while (!$prefix && ! empty($possibleNames)) {
                $prefix = Utils::findBin(array_pop($possibleNames));
            }

            $opts[] = $prefix ? "--with-pgsql=$prefix" : "--with-pgsql";

            if ($build->hasVariant('pdo')) {
                $opts[] = $prefix ? "--with-pdo-pgsql=$prefix" : '--with-pdo-pgsql';
            }

            return $opts;
        };


        $this->variants['xml'] = function (Build $build) {
            $options = array(
                '--enable-dom',
                '--enable-libxml',
                '--enable-simplexml',
                '--enable-xml',
                '--enable-xmlreader',
                '--enable-xmlwriter',
                '--with-xsl'
            );

            if ($prefix = Utils::getPkgConfigPrefix('libxml')) {
                $options[] = "--with-libxml-dir=$prefix";
            } else if ($prefix = Utils::findIncludePrefix('libxml2/libxml/globals.h')) {
                $options[] = "--with-libxml-dir=$prefix";
            } else if ($prefix = Utils::findLibPrefix('libxml2.a')) {
                $options[] = "--with-libxml-dir=$prefix";
            } else if ($bin = Utils::findBin('brew')) {
                exec("$bin --prefix libxml2", $output, $retval);
                if ($retval === 0 && !empty($output)) {
                    $options[] = '--with-libxml-dir=' . end(array_filter($prefix));
                }
            }

            return $options;
        };
        $this->variants['xml_all'] = $this->variants['xml'];

        $this->variants['apxs2'] = function (Build $build, $prefix = null) {
            $a = '--with-apxs2';
            if ($prefix) {
                return '--with-apxs2=' . $prefix;
            }

            if ($bin = Utils::findBinByPrefix('apxs2')) {
                return '--with-apxs2=' . $bin;
            } else if ($bin = Utils::findBinByPrefix('apxs')) {
                return '--with-apxs2=' . $bin;
            }

            /* Special paths for homebrew */
            $possiblePaths = array(
                // macports apxs path
                '/usr/local/opt/httpd24/bin/apxs',
                '/usr/local/opt/httpd23/bin/apxs',
                '/usr/local/opt/httpd22/bin/apxs',
                '/usr/local/opt/httpd21/bin/apxs',

                '/usr/local/sbin/apxs', // homebrew apxs prefix
                '/usr/local/bin/apxs',
                '/usr/sbin/apxs', // it's possible to find apxs under this path (OS X)
                '/usr/bin/apxs', // not sure if this one helps
            );
            $paths = array_filter($possiblePaths, "file_exists");
            if (count($paths) > 0 && file_exists($paths[0])) {
                $opts[] = "--with-apxs2={$paths[0]}";
            }
            return $a; // fallback to autoconf finder
        };


        $this->variants['gettext'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return '--with-gettext=' . $prefix;
            }

            if ($prefix = Utils::findIncludePrefix('libintl.h')) {
                return '--with-gettext=' . $prefix;
            }
            if (file_exists('/usr/local/opt/gettext')) {
                return '--with-gettext=/usr/local/opt/gettext';
            }
            return '--with-gettext';
        };


        $this->variants['iconv'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-iconv=$prefix";
            }
            /*
             * php can't be compile with --with-iconv=/usr because it uses giconv
             *
             * https://bugs.php.net/bug.php?id=48451
             *
            // detect include path for iconv.h
            if ( $prefix = Utils::find_include_prefix('giconv.h', 'iconv.h') ) {
                return "--with-iconv=$prefix";
            }
            */

            return "--with-iconv";
        };

        $this->variants['bz2'] = function ($build, $prefix = null) {
            if ($prefix) {
                return "--with-bz2=$prefix";
            }

            if ($prefix = Utils::findIncludePrefix('bzlib.h')) {
                return "--with-bz2=$prefix";
            }

            return '--with-bz2';
        };

        $this->variants['ipc'] = function (Build $build) {
            return array(
                '--enable-shmop',
                '--enable-sysvsem',
                '--enable-sysvshm',
                '--enable-sysvmsg',
            );
        };

        $this->variants['gmp'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-gmp=$prefix";
            }

            if ($prefix = Utils::findIncludePrefix('gmp.h')) {
                return "--with-gmp=$prefix";
            }

            return "--with-gmp"; // let autotool to find it.
        };

        // merge virtual variants with config file
        $customVirtualVariants = Config::getConfigParam('variants');
        $customVirtualVariantsToAdd = array();

        if (!empty($customVirtualVariants)) {
            foreach ($customVirtualVariants as $key => $extension) {
                // The extension might be null
                if (!empty($extension)) {
                    $customVirtualVariantsToAdd[$key] = array_keys($extension);
                }
            }
        }

        $this->virtualVariants = array_merge($customVirtualVariantsToAdd, $this->virtualVariants);

        // create +everything variant
        $this->virtualVariants['everything'] = array_diff(
            array_keys($this->variants),
            array('apxs2', 'all') // <- except these ones
        );
    }

    private function getConflict(Build $build, $feature)
    {
        if (isset($this->conflicts[ $feature ])) {
            $conflicts = array();

            foreach ($this->conflicts[ $feature ] as $f) {
                if ($build->isEnabledVariant($f)) {
                    $conflicts[] = $f;
                }
            }

            return $conflicts;
        }

        return false;
    }

    public function checkConflicts(Build $build)
    {
        if ($build->isEnabledVariant('apxs2') && version_compare($build->getVersion(), '5.4.0') < 0) {
            if ($conflicts = $this->getConflict($build, 'apxs2')) {
                $msgs = array();
                $msgs[] = "PHP Version lower than 5.4.0 can only build one SAPI at the same time.";
                $msgs[] = "+apxs2 is in conflict with " . join(',', $conflicts);

                foreach ($conflicts as $c) {
                    $msgs[] = "Disabling $c";
                    $build->disableVariant($c);
                }

                echo join("\n", $msgs) . "\n";
            }
        }

        return true;
    }

    public function checkPkgPrefix($option, $pkgName)
    {
        $prefix = Utils::getPkgConfigPrefix($pkgName);

        return $prefix ? $option . '=' . $prefix : $option;
    }

    public function getVariantNames()
    {
        return array_keys($this->variants);
    }

    /**
     * Build options from variant
     *
     * @param Build  $build
     * @param string $feature   variant name
     * @param string $userValue option value.
     *
     * @return array
     *
     * @throws OopsException
     * @throws Exception
     */
    public function buildVariant(Build $build, $feature, $userValue = null)
    {
        if (!isset($this->variants[ $feature ])) {
            throw new Exception("Variant '$feature' is not defined.");
        }

        // Skip if we've built it
        if (in_array($feature, $this->builtList)) {
            return array();
        }

        // Skip if we've disabled it
        if (isset($this->disables[$feature])) {
            return array();
        }

        $this->builtList[] = $feature;
        $cb = $this->variants[ $feature ];

        if (is_array($cb)) {
            return $cb;
        } elseif (is_string($cb)) {
            return array($cb);
        } elseif (is_callable($cb)) {
            $args = is_string($userValue) ? array($build,$userValue) : array($build);

            return (array) call_user_func_array($cb, $args);
        } else {
            throw new OopsException();
        }
    }

    public function buildDisableVariant(Build $build, $feature, $userValue = null)
    {
        if (isset($this->variants[$feature])) {
            if (in_array('-'.$feature, $this->builtList)) {
                return array();
            }

            $this->builtList[] = '-'.$feature;
            $func = $this->variants[ $feature ];

            // build the option from enabled variant,
            // then convert the '--enable' and '--with' options
            // to '--disable' and '--without'
            $args = is_string($userValue) ? array($build,$userValue) : array($build);

            if (is_string($func)) {
                $disableOptions = (array) $func;
            } elseif (is_callable($func)) {
                $disableOptions = (array) call_user_func_array($func, $args);
            } else {
                throw new Exception("Unsupported variant handler type. neither string nor callable.");
            }

            $resultOptions = array();

            foreach ($disableOptions as $option) {
                // strip option value after the equal sign '='
                $option = preg_replace("/=.*$/", "", $option);

                // convert --enable-xxx to --disable-xxx
                $option = preg_replace("/^--enable-/", "--disable-", $option);

                // convert --with-xxx to --without-xxx
                $option = preg_replace("/^--with-/", "--without-", $option);
                $resultOptions[] = $option;
            }

            return $resultOptions;
        }

        throw new Exception("Variant $feature is not defined.");
    }

    public function addOptions($options)
    {
        // skip false value
        if (! $options) {
            return;
        }

        if (is_string($options)) {
            $this->options[] = $options;
        } else {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * Build variants to configure options from php build object.
     *
     * @param Build $build The build object, contains version information
     *
     * @return array|void
     * @throws \Exception
     */
    public function build(Build $build)
    {
        $customVirtualVariants = Config::getConfigParam('variants');
        foreach (array_keys($build->getVariants()) as $variantName) {
            if (isset($customVirtualVariants[$variantName])) {
                foreach ($customVirtualVariants[$variantName] as $lib => $params) {
                    if (is_array($params)) {
                        $this->variants[$lib] = $params;
                    }
                }
            }
        }

        // reset builtList
        $this->builtList = array();

        // reset built options
        if ($build->hasVariant('all') || $build->hasVariant('neutral')) {
            $this->options = array();
        } else {
            // build common options
            $this->options = array(
                '--disable-all',
                '--enable-phar',
                '--enable-session',
                '--enable-short-tags',
                '--enable-tokenizer',
                '--with-pcre-regex',
            );

            if ($prefix = Utils::findIncludePrefix('zlib.h')) {
                $this->addOptions('--with-zlib=' . $prefix);
            }
        }

        if ($prefix = Utils::findLibPrefix('x86_64-linux-gnu')) {
            $this->addOptions("--with-libdir=lib/x86_64-linux-gnu");
        } elseif ($prefix = Utils::findLibPrefix('i386-linux-gnu')) {
            $this->addOptions("--with-libdir=lib/i386-linux-gnu");
        }

        if ($build->compareVersion('5.6') >= 0) {
            $build->enableVariant('opcache');
        }


        // enable/expand virtual variants
        foreach ($this->virtualVariants as $name => $variantNames) {
            if ($build->isEnabledVariant($name)) {
                foreach ($variantNames as $subVariantName) {
                    // enable the sub-variant only if it's not already enabled
                    // in order to not override a non-default value with the default
                    if (!$build->isEnabledVariant($subVariantName)) {
                        $build->enableVariant($subVariantName);
                    }
                }

                // it's a virtual variant, can not be built by buildVariant
                // method.
                $build->removeVariant($name);
            }
        }

        // Remove these enabled variant for disabled variants.
        $build->resolveVariants();

        // before we build these options from variants,
        // we need to check the enabled and disabled variants
        $this->checkConflicts($build);

        foreach ($build->getVariants() as $feature => $userValue) {
            if ($options = $this->buildVariant($build, $feature, $userValue)) {
                $this->addOptions($options);
            }
        }

        foreach ($build->getDisabledVariants() as $feature => $true) {
            if ($options = $this->buildDisableVariant($build, $feature)) {
                $this->addOptions($options);
            }
        }

        /*
        $opts = array_merge( $opts ,
            $this->getVersionSpecificOptions($version) );
        */
        $options =  array_merge(array(), $this->options);

        // reset options
        $this->options = array();

        return $options;
    }
}

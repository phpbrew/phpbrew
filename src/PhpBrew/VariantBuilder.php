<?php

namespace PhpBrew;

use Exception;
use PhpBrew\Exception\OopsException;
use PhpBrew\PrefixFinder\BrewPrefixFinder;
use PhpBrew\PrefixFinder\IncludePrefixFinder;
use PhpBrew\PrefixFinder\LibPrefixFinder;
use PhpBrew\PrefixFinder\PkgConfigPrefixFinder;

function first_existing_executable($possiblePaths)
{
    $existingPaths = array_filter(
        array_filter(
            array_filter($possiblePaths, 'file_exists'),
            'is_file'
        ),
        'is_executable'
    );

    if (!empty($existingPaths)) {
        return realpath($existingPaths[0]);
    }

    return false;
}

function exec_line($command)
{
    $output = array();
    exec($command, $output, $retval);
    if ($retval === 0) {
        $output = array_filter($output);

        return end($output);
    }

    return false;
}

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
     * available variants.
     */
    public $variants = array();

    public $conflicts = array(
        // PHP Version lower than 5.4.0 can only built one SAPI at the same time.
        'apxs2' => array('fpm', 'cgi'),
        'editline' => array('readline'),
        'readline' => array('editline'),

        // dtrace is not compatible with phpdbg: https://github.com/krakjoe/phpdbg/issues/38
        'dtrace' => array('phpdbg'),
    );

    public $options = array();

    /**
     * @var array is for checking built variants
     *
     * contains ['-pdo','mysql','-sqlite','-debug']
     */
    public $builtList = array();

    public $virtualVariants = array(
        'dbs' => array(
            'sqlite',
            'mysql',
            'pgsql',
            'pdo',
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
            'pcntl',
            'pcre',
            'pdo',
            'pear',
            'phar',
            'posix',
            'readline',
            'sockets',
            'sodium',
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
        $this->variants['all'] = '--enable-all';
        $this->variants['dba'] = '--enable-dba';
        $this->variants['ipv6'] = '--enable-ipv6';
        $this->variants['dom'] = '--enable-dom';
        $this->variants['calendar'] = '--enable-calendar';
        $this->variants['wddx'] = '--enable-wddx';
        $this->variants['static'] = '--enable-static';
        $this->variants['inifile'] = '--enable-inifile';
        $this->variants['inline'] = '--enable-inline-optimization';

        $this->variants['cli'] = '--enable-cli';

        $this->variants['ftp'] = '--enable-ftp';
        $this->variants['filter'] = '--enable-filter';
        $this->variants['gcov'] = '--enable-gcov';
        $this->variants['zts'] = '--enable-maintainer-zts';

        $this->variants['json'] = '--enable-json';
        $this->variants['hash'] = '--enable-hash';
        $this->variants['exif'] = '--enable-exif';
        $this->variants['mbstring'] = '--enable-mbstring';
        $this->variants['mbregex'] = '--enable-mbregex';
        $this->variants['libgcc'] = '--enable-libgcc';
        // $this->variants['gd-jis'] = '--enable-gd-jis-conv';

        $this->variants['pdo'] = '--enable-pdo';
        $this->variants['posix'] = '--enable-posix';
        $this->variants['embed'] = '--enable-embed';
        $this->variants['sockets'] = '--enable-sockets';
        $this->variants['debug'] = '--enable-debug';
        $this->variants['phpdbg'] = '--enable-phpdbg';

        $this->variants['zip'] = function (Build $build) {
            if ($build->compareVersion('7.4') < 0) {
                return '--enable-zip';
            }

            return '--with-zip';
        };

        $this->variants['bcmath'] = '--enable-bcmath';
        $this->variants['fileinfo'] = '--enable-fileinfo';
        $this->variants['ctype'] = '--enable-ctype';
        $this->variants['cgi'] = '--enable-cgi';
        $this->variants['soap'] = '--enable-soap';
        $this->variants['gcov'] = '--enable-gcov';
        $this->variants['pcntl'] = '--enable-pcntl';

        $this->variants['phar'] = '--enable-phar';
        $this->variants['session'] = '--enable-session';
        $this->variants['tokenizer'] = '--enable-tokenizer';

        // opcache was added since 5.6
        $this->variants['opcache'] = '--enable-opcache';

        $this->variants['imap'] = '--with-imap-ssl';
        $this->variants['ldap'] = '--with-ldap';
        $this->variants['tidy'] = '--with-tidy';
        $this->variants['kerberos'] = '--with-kerberos';
        $this->variants['xmlrpc'] = '--with-xmlrpc';

        $this->variants['fpm'] = function (Build $build, $prefix = null) {
            $opts = array('--enable-fpm');
            if ($bin = Utils::findBin('systemctl') && Utils::findIncludePrefix('systemd/sd-daemon.h')) {
                $opts[] = '--with-fpm-systemd';
            }
            return $opts;
        };

        $this->variants['dtrace'] = function (Build $build, $prefix = null) {
            // if dtrace is supported
            /*
            if ($prefix = Utils::findIncludePrefix('sys/sdt.h')) {
                return "--enable-dtrace";
            }
            */
            return '--enable-dtrace';
        };

        $this->variants['pcre'] = function (Build $build, $prefix = null) {
            if ($build->compareVersion('7.4') >= 0) {
                return array();
            }

            if ($prefix) {
                return array('--with-pcre-regex', "--with-pcre-dir=$prefix");
            }
            if ($prefix = Utils::findIncludePrefix('pcre.h')) {
                return array('--with-pcre-regex', "--with-pcre-dir=$prefix");
            }
            if ($bin = Utils::findBin('brew')) {
                if ($prefix = exec_line("$bin --prefix pcre")) {
                    if (file_exists($prefix)) {
                        return array('--with-pcre-regex', "--with-pcre-dir=$prefix");
                    }

                    printf('Homebrew prefix "%s" doesn\'t exist' . PHP_EOL, $prefix);
                }
            }

            return array('--with-pcre-regex');
        };

        $this->variants['mhash'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-mhash=$prefix";
            }

            if ($prefix = Utils::findIncludePrefix('mhash.h')) {
                return "--with-mhash=$prefix";
            }
            if ($bin = Utils::findBin('brew')) {
                if ($output = exec_line("$bin --prefix mhash")) {
                    if (file_exists($output)) {
                        return "--with-mhash=$output";
                    }
                    echo "homebrew prefix '$output' doesn't exist. you forgot to install?\n";
                }
            }

            return '--with-mhash'; // let autotool to find it.
        };

        $this->variants['mcrypt'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-mcrypt=$prefix";
            }

            if ($prefix = Utils::findIncludePrefix('mcrypt.h')) {
                return "--with-mcrypt=$prefix";
            }

            if ($bin = Utils::findBin('brew')) {
                if ($output = exec_line("$bin --prefix mcrypt")) {
                    if (file_exists($output)) {
                        return "--with-mcrypt=$output";
                    }
                    echo "homebrew prefix '$output' doesn't exist. you forgot to install?\n";
                }
            }

            return '--with-mcrypt'; // let autotool to find it.
        };

        $this->variants['zlib'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-zlib=$prefix";
            }
            if ($prefix = Utils::findIncludePrefix('zlib.h')) {
                return "--with-zlib=$prefix";
            }

            return;
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
                if ($prefix = exec_line("$bin --prefix curl")) {
                    if (file_exists($prefix)) {
                        return "--with-curl=$prefix";
                    }
                    echo "homebrew prefix '$prefix' doesn't exist. you forgot to install?\n";
                }
            }
            if ($bin = Utils::findBin('curl-config')) {
                if ($prefix = exec_line("$bin --prefix")) {
                    if (file_exists($prefix)) {
                        return "--with-curl=$prefix";
                    }
                    echo "homebrew prefix '$prefix' doesn't exist. you forgot to install?\n";
                }
            }

            return '--with-curl';
        };

        /*
        Users might prefer readline over libedit because only readline supports
        readline_list_history() (http://www.php.net/readline-list-history).
        On the other hand we want libedit to be the default because its license
        is compatible with PHP's which means PHP can be distributable.

        related issue https://github.com/phpbrew/phpbrew/issues/497

        The default libreadline version that comes with OS X is too old and
        seems to be missing symbols like rl_mark, rl_pending_input,
        rl_history_list, rl_on_new_line. This is not detected by ./configure

        So we should prefer macports/homebrew library than the system readline library.
        @see https://bugs.php.net/bug.php?id=48608
        */
        $this->variants['readline'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-readline=$prefix";
            }
            if ($bin = Utils::findBin('brew')) {
                if ($output = exec_line("$bin --prefix readline")) {
                    if (file_exists($output)) {
                        return '--with-readline=' . $output;
                    }
                    echo "homebrew prefix '$output' doesn't exist. you forgot to install?\n";
                }
            }
            if ($prefix = Utils::findIncludePrefix('readline' . DIRECTORY_SEPARATOR . 'readline.h')) {
                return '--with-readline=' . $prefix;
            }
            return '--with-readline';
        };

        /*
         * editline is conflict with readline
         *
         * one must tap the homebrew/dupes to use this formula
         *
         *      brew tap homebrew/dupes
         */
        $this->variants['editline'] = function (Build $build, $prefix = null) {
            if ($prefix) {
                return "--with-libedit=$prefix";
            } elseif ($prefix = Utils::findIncludePrefix('editline' . DIRECTORY_SEPARATOR . 'readline.h')) {
                return "--with-libedit=$prefix";
            } elseif ($bin = Utils::findBin('brew')) {
                if ($output = exec_line("$bin --prefix libedit")) {
                    if (file_exists($output)) {
                        return '--with-libedit=' . $output;
                    }
                    echo "homebrew prefix '$output' doesn't exist. you forgot to install?\n";
                } else {
                    echo "prefix of libedit not found, please run 'brew tap homebrew/dupes' to get the formula\n";
                }
            }
            return '--with-libedit';
        };

        /*
         * It looks like gd won't be compiled without "shared"
         *
         * Suggested options is +gd=shared,{prefix}
         *
         * Issue: gd.so: undefined symbol: gdImageCreateFromWebp might happend
         *
         * Adding --with-libpath=lib or --with-libpath=lib/x86_64-linux-gnu
         * might solve the gd issue.
         *
         * The configure script in ext/gd detects libraries by something like
         * test -f $PREFIX/$LIBPATH/libxxx.a, where $PREFIX is what you passed
         * in --with-xxxx-dir and $LIBPATH can varies in different OS.
         *
         * By adding --with-libpath, you can set it up properly.
         *
         * @see https://github.com/phpbrew/phpbrew/issues/461
         */
        $this->variants['gd'] = function (Build $build, $prefix = null) {
            if ($prefix === null) {
                $prefix = Utils::findPrefix(array(
                    new IncludePrefixFinder('gd.h'),
                    new BrewPrefixFinder('gd'),
                ));
            }

            if ($build->compareVersion('7.4') < 0) {
                $flag = '--with-gd';
            } else {
                $flag = '--enable-gd';
            }

            $value = 'shared';

            if ($prefix !== null) {
                $value .= ',' . $prefix;
            }

            $opts = array(sprintf('%s=%s', $flag, $value));

            if ($build->compareVersion('5.5') < 0) {
                $opts[] = '--enable-gd-native-ttf';
            }

            if (
                ($prefix = Utils::findPrefix(array(
                new IncludePrefixFinder('jpeglib.h'),
                new BrewPrefixFinder('libjpeg'),
                ))) !== null
            ) {
                if ($build->compareVersion('7.4') < 0) {
                    $flag = '--with-jpeg-dir';
                } else {
                    $flag = '--with-jpeg';
                }

                $opts[] = sprintf('%s=%s', $flag, $prefix);
            }

            if (
                $build->compareVersion('7.4') < 0 && ($prefix = Utils::findPrefix(array(
                new IncludePrefixFinder('png.h'),
                new IncludePrefixFinder('libpng12/pngconf.h'),
                new BrewPrefixFinder('libpng'),
                ))) !== null
            ) {
                $opts[] = '--with-png-dir=' . $prefix;
            }

            // the freetype-dir option does not take prefix as its value,
            // it takes the freetype.h directory as its value.
            //
            // from configure:
            //   for path in $i/include/freetype2/freetype/freetype.h
            if (
                ($prefix = Utils::findPrefix(array(
                new IncludePrefixFinder('freetype2/freetype.h'),
                new IncludePrefixFinder('freetype2/freetype/freetype.h'),
                new BrewPrefixFinder('freetype'),
                ))) !== null
            ) {
                if ($build->compareVersion('7.4') < 0) {
                    $flag = '--with-freetype-dir';
                } else {
                    $flag = '--with-freetype';
                }

                $opts[] = sprintf('%s=%s', $flag, $prefix);
            }

            return $opts;
        };

        /*
        --enable-intl

         To build the extension you need to install the » ICU library, version
         4.0.0 or newer is required.

         This extension is bundled with PHP as of PHP version 5.3.0.
         Alternatively, the PECL version of this extension may be used with all
         PHP versions greater than 5.2.0 (5.2.4+ recommended).

         This requires --with-icu-dir=/....

         Please note prefix must provide {prefix}/bin/icu-config for autoconf
         to find the related icu-config binary, or the configure will fail.

         Issue: https://github.com/phpbrew/phpbrew/issues/433
        */
        $this->variants['intl'] = function (Build $build) {
            $opts = array('--enable-intl');

            if ($build->compareVersion('7.4') >= 0) {
                return $opts;
            }

            // If icu variant is not set, and --with-icu-dir could not been found in the extra options
            $icuOption = $build->settings->grepExtraOptionsByPattern('#--with-icu-dir#');
            if (!$build->settings->isEnabledVariant('icu') || empty($icuOption)) {
                if ($bin = Utils::findBin('icu-config')) {
                    /*
                    * let autoconf find it's own icu-config
                    * The build-in acinclude.m4 will find the icu-config from $PATH:/usr/local/bin
                    */
                } elseif ($prefix = Utils::getPkgConfigPrefix('icu-i18n')) {
                    // For macports or linux
                    $opts[] = '--with-icu-dir=' . $prefix;
                } elseif ($bin = Utils::findBin('brew')) {
                    // For homebrew
                    if ($output = exec_line("$bin --prefix icu4c")) {
                        if (file_exists($output)) {
                            $opts[] = "--with-icu-dir=$output";
                        } else {
                            echo "homebrew prefix '$output' doesn't exist. you forgot to install?\n";
                        }
                    }
                }
            }

            return $opts;
        };

        /*
         * icu variant
         *
         * @deprecated this variant is deprecated since icu is a part of intl
         * extension.  however, we kept this variant for user to customize the icu path
         */
        $this->variants['icu'] = function (Build $build, $val = null) {
            if ($val) {
                return '--with-icu-dir=' . $val;
            }
        };

        $this->variants['sodium'] = function (Build $build, $prefix = null) {
            if ($build->compareVersion('7.2') < 0) {
                echo "Sodium is available as a core extension since PHP 7.2.0. Please use 'phpbrew ext install sodium' to install it from PECL\n";
            }

            if ($prefix === null) {
                $prefix = Utils::findPrefix(array(
                    new BrewPrefixFinder('libsodium'),
                    new PkgConfigPrefixFinder('libsodium'),
                    new IncludePrefixFinder('sodium.h'),
                    new LibPrefixFinder('libsodium.a'),
                ));
            }

            if ($prefix !== null) {
                return '--with-sodium=' . $prefix;
            } else {
                return '--with-sodium';
            }
        };

        /*
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

            // Special detection and fallback for homebrew openssl
            // @see https://github.com/phpbrew/phpbrew/issues/607
            if ($bin = Utils::findBin('brew')) {
                if ($output = exec_line("$bin --prefix openssl")) {
                    if (file_exists($output)) {
                        return "--with-openssl=$output";
                    }
                    echo "prefix $output doesn't exist.";
                }
            }

            if ($prefix = Utils::getPkgConfigPrefix('openssl')) {
                return "--with-openssl=$prefix";
            }

            $possiblePrefixes = array('/usr/local/opt/openssl');
            $foundPrefixes = array_filter($possiblePrefixes, 'file_exists');
            if (count($foundPrefixes) > 0) {
                return '--with-openssl=' . $foundPrefixes[0];
            }

            // This will create openssl.so file for dynamic loading.
            echo 'Compiling with openssl=shared, please install libssl-dev or openssl header files if you need';

            return '--with-openssl';
        };

        /*
        quote from the manual page:

        > MySQL Native Driver is a replacement for the MySQL Client Library
        > (libmysqlclient). MySQL Native Driver is part of the official PHP
        > sources as of PHP 5.3.0.

        > The MySQL database extensions MySQL extension, mysqli and PDO MYSQL all
        > communicate with the MySQL server. In the past, this was done by the
        > extension using the services provided by the MySQL Client Library. The
        > extensions were compiled against the MySQL Client Library in order to
        > use its client-server protocol.

        > With MySQL Native Driver there is now an alternative, as the MySQL
        > database extensions can be compiled to use MySQL Native Driver instead
        > of the MySQL Client Library.

        mysqlnd should be prefered over the native client library.

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

        --enable-mysqlnd        Enable mysqlnd explicitly, will be done implicitly
                                when required by other extensions

        mysqlnd was added since php 5.3
        */
        $this->variants['mysql'] = function (Build $build, $prefix = 'mysqlnd') {
            $opts = array();
            if ($build->compareVersion('7.0') < 0) {
                $opts[] = "--with-mysql=$prefix";
            }

            /*
            if ($build->compareVersion('5.4') > 0) {
                $opts[] = "--enable-mysqlnd";
            }
            */
            $opts[] = "--with-mysqli=$prefix";
            if ($build->hasVariant('pdo')) {
                $opts[] = "--with-pdo-mysql=$prefix";
            }

            $foundSock = false;
            if ($bin = Utils::findBin('mysql_config')) {
                if ($output = exec_line("$bin --socket")) {
                    $foundSock = true;
                    $opts[] = "--with-mysql-sock=$output";
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
                    '/var/run/mysqld/mysqld.sock', /* ubuntu */
                    '/var/mysql/mysql.sock',
                );

                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $opts[] = '--with-mysql-sock=' . $path;
                        break;
                    }
                }
            }

            return $opts;
        };

        $this->variants['sqlite'] = function (Build $build, $prefix = null) {
            $opts = array(
                '--with-sqlite3' . ($prefix ? "=$prefix" : ''),
            );

            if ($build->hasVariant('pdo')) {
                $opts[] = '--with-pdo-sqlite';
            }

            return $opts;
        };



        /**
         * The --with-pgsql=[DIR] and --with-pdo-pgsql=[DIR] requires [DIR]/bin/pg_config to be found.
         */
        $this->variants['pgsql'] = function (Build $build, $prefix = null) {
            $opts = array();

            // The names are used from macports
            if ($prefix) {
                $opts[] = "--with-pgsql=$prefix";
                if ($build->hasVariant('pdo')) {
                    $opts[] = "--with-pdo-pgsql=$prefix";
                }
                return $opts;
            }

            $bin = Utils::findBin('pg_config');
            if (!$bin) {
                $bin = first_existing_executable(array(
                        '/opt/local/lib/postgresql95/bin/pg_config',
                        '/opt/local/lib/postgresql94/bin/pg_config',
                        '/opt/local/lib/postgresql93/bin/pg_config',
                        '/opt/local/lib/postgresql92/bin/pg_config',
                        '/Library/PostgreSQL/9.5/bin/pg_config',
                        '/Library/PostgreSQL/9.4/bin/pg_config',
                        '/Library/PostgreSQL/9.3/bin/pg_config',
                        '/Library/PostgreSQL/9.2/bin/pg_config',
                        '/Library/PostgreSQL/9.1/bin/pg_config',
                    ));
            }

            if ($bin) {
                $opts[] = "--with-pgsql=" . dirname($bin);
                if ($build->hasVariant('pdo')) {
                    $opts[] = "--with-pdo-pgsql=" . dirname($bin);
                }
                return $opts;
            }

            $opts[] = "--with-pgsql";
            if ($build->hasVariant('pdo')) {
                $opts[] = '--with-pdo-pgsql';
            }
            return $opts;
        };

        $this->variants['xml'] = function (Build $build) {
            $options = array(
                '--enable-dom',
            );

            if ($build->compareVersion('7.4') < 0) {
                $options[] = '--enable-libxml';

                if (
                    ($prefix = Utils::findPrefix(array(
                    new BrewPrefixFinder('libxml2'),
                    new PkgConfigPrefixFinder('libxml'),
                    new IncludePrefixFinder('libxml2/libxml/globals.h'),
                    new LibPrefixFinder('libxml2.a'),
                    ))) !== null
                ) {
                    $options[] = '--with-libxml-dir=' . $prefix;
                }
            } else {
                $options[] = '--with-libxml';
            }

            $options = array_merge($options, array(
                '--enable-simplexml',
                '--enable-xml',
                '--enable-xmlreader',
                '--enable-xmlwriter',
                '--with-xsl',
            ));

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
            } elseif ($bin = Utils::findBinByPrefix('apxs')) {
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
            if ($path = first_existing_executable($possiblePaths)) {
                $opts[] = "--with-apxs2=$path";
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

            if ($bin = Utils::findBin('brew')) {
                if ($output = exec_line("$bin --prefix gettext")) {
                    if (file_exists($output)) {
                        return "--with-gettext=$output";
                    }
                    echo "homebrew prefix '$output' doesn't exist. you forgot to install?\n";
                }
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
            return '--with-iconv';
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

            return '--with-gmp'; // let autotool to find it.
        };

        $this->variants['pear'] = function (Build $build, $prefix = null) {
            if ($prefix === null) {
                $prefix = $build->getInstallPrefix() . '/lib/php/pear';
            }

            return '--with-pear=' . $prefix;
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
                $msgs[] = 'PHP Version lower than 5.4.0 can only build one SAPI at the same time.';
                $msgs[] = '+apxs2 is in conflict with ' . implode(',', $conflicts);

                foreach ($conflicts as $c) {
                    $msgs[] = "Disabling $c";
                    $build->disableVariant($c);
                }

                echo implode("\n", $msgs) . "\n";
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
     * Build options from variant.
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
            $args = is_string($userValue) ? array($build, $userValue) : array($build);

            return (array) call_user_func_array($cb, $args);
        } else {
            throw new OopsException();
        }
    }

    public function buildDisableVariant(Build $build, $feature, $userValue = null)
    {
        if (isset($this->variants[$feature])) {
            if (in_array('-' . $feature, $this->builtList)) {
                return array();
            }

            $this->builtList[] = '-' . $feature;
            $func = $this->variants[ $feature ];

            // build the option from enabled variant,
            // then convert the '--enable' and '--with' options
            // to '--disable' and '--without'
            $args = is_string($userValue) ? array($build, $userValue) : array($build);

            if (is_string($func)) {
                $disableOptions = (array) $func;
            } elseif (is_callable($func)) {
                $disableOptions = (array) call_user_func_array($func, $args);
            } else {
                throw new Exception('Unsupported variant handler type. neither string nor callable.');
            }

            $resultOptions = array();

            foreach ($disableOptions as $option) {
                // strip option value after the equal sign '='
                $option = preg_replace('/=.*$/', '', $option);

                // convert --enable-xxx to --disable-xxx
                $option = preg_replace('/^--enable-/', '--disable-', $option);

                // convert --with-xxx to --without-xxx
                $option = preg_replace('/^--with-/', '--without-', $option);
                $resultOptions[] = $option;
            }

            return $resultOptions;
        }

        throw new Exception("Variant $feature is not defined.");
    }

    public function addOptions($options)
    {
        // skip false value
        if (!$options) {
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
     *
     * @throws Exception
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
            );

            if ($build->compareVersion('7.4') < 0) {
                $this->addOptions('--with-pcre-regex');
            }

            if ($prefix = Utils::findIncludePrefix('zlib.h')) {
                $this->addOptions('--with-zlib=' . $prefix);
            }
        }

        if ($prefix = Utils::findLibPrefix('x86_64-linux-gnu')) {
            $this->addOptions('--with-libdir=lib/x86_64-linux-gnu');
        } elseif ($prefix = Utils::findLibPrefix('i386-linux-gnu')) {
            $this->addOptions('--with-libdir=lib/i386-linux-gnu');
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
        $options = array_merge(array(), $this->options);

        // reset options
        $this->options = array();

        return $options;
    }
}

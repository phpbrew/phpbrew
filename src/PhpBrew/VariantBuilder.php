<?php

namespace PhpBrew;

use Exception;
use PhpBrew\Exception\OopsException;
use PhpBrew\PrefixFinder\BrewPrefixFinder;
use PhpBrew\PrefixFinder\ExecutablePrefixFinder;
use PhpBrew\PrefixFinder\IncludePrefixFinder;
use PhpBrew\PrefixFinder\LibPrefixFinder;
use PhpBrew\PrefixFinder\PkgConfigPrefixFinder;
use PhpBrew\PrefixFinder\UserProvidedPrefix;

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
 * VariantBuilder build variants to `./configure' parameters.
 */
class VariantBuilder
{
    /**
     * Available variant definitions.
     *
     * @var array<string,string|array<string>|callable>
     */
    private $variants = array();

    private $conflicts = array(
        // PHP Version lower than 5.4.0 can only built one SAPI at the same time.
        'apxs2' => array('fpm', 'cgi'),
        'editline' => array('readline'),
        'readline' => array('editline'),

        // dtrace is not compatible with phpdbg: https://github.com/krakjoe/phpdbg/issues/38
        'dtrace' => array('phpdbg'),
    );

    /**
     * @var array is for checking built variants
     *
     * contains ['-pdo','mysql','-sqlite','-debug']
     */
    private $builtList = array();

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
        $this->variants['zts'] = function (ConfigureParameters $params, Build $build) {
            if ($build->compareVersion('8.0') < 0) {
                return $params->withOption('--enable-maintainer-zts');
            }

            return $params->withOption('--enable-zts');
        };

        $this->variants['json'] = function (ConfigureParameters $params, Build $build) {
            if ($build->compareVersion('8.0') < 0) {
                return $params->withOption('--enable-json');
            }

            return $params;
        };
        $this->variants['hash'] = '--enable-hash';
        $this->variants['exif'] = '--enable-exif';

        $this->variants['mbstring'] = function (ConfigureParameters $params, Build $build, $value) {
            $params = $params->withOption('--enable-mbstring');

            if ($build->compareVersion('7.4') >= 0 && !$build->isDisabledVariant('mbregex')) {
                $prefix = Utils::findPrefix(array(
                    new UserProvidedPrefix($value),
                    new IncludePrefixFinder('oniguruma.h'),
                    new BrewPrefixFinder('oniguruma'),
                ));

                if ($prefix !== null) {
                    $params = $params->withPkgConfigPath($prefix . '/lib/pkgconfig');
                }
            }

            return $params;
        };

        $this->variants['mbregex'] = '--enable-mbregex';
        $this->variants['libgcc'] = '--enable-libgcc';

        $this->variants['pdo'] = '--enable-pdo';
        $this->variants['posix'] = '--enable-posix';
        $this->variants['embed'] = '--enable-embed';
        $this->variants['sockets'] = '--enable-sockets';
        $this->variants['debug'] = '--enable-debug';
        $this->variants['phpdbg'] = '--enable-phpdbg';

        $this->variants['zip'] = function (ConfigureParameters $params, Build $build) {
            if ($build->compareVersion('7.4') < 0) {
                return $params->withOption('--enable-zip');
            }

            return $params->withOption('--with-zip');
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

        $this->variants['opcache'] = '--enable-opcache';

        $this->variants['imap'] = function (ConfigureParameters $params, Build $build, $value) {
            $imapPrefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('imap-uw'),
            ));

            $kerberosPrefix = Utils::findPrefix(array(
                new BrewPrefixFinder('krb5'),
            ));

            $opensslPrefix = Utils::findPrefix(array(
                new BrewPrefixFinder('openssl'),
                new PkgConfigPrefixFinder('openssl'),
                new IncludePrefixFinder('openssl/opensslv.h'),
            ));

            return $params->withOption('--with-imap', $imapPrefix)
                ->withOptionOrPkgConfigPath($build, '--with-kerberos', $kerberosPrefix)
                ->withOptionOrPkgConfigPath($build, '--with-imap-ssl', $opensslPrefix);
        };

        $this->variants['ldap'] = function (ConfigureParameters $params, Build $_, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('ldap.h'),
                new BrewPrefixFinder('openldap'),
            ));

            if ($prefix !== null) {
                $params = $params->withOption('--with-ldap', $prefix);
            }

            return $params;
        };

        $this->variants['tidy'] = '--with-tidy';
        $this->variants['kerberos'] = '--with-kerberos';
        $this->variants['xmlrpc'] = '--with-xmlrpc';

        $this->variants['fpm'] = function (ConfigureParameters $params) {
            $params = $params->withOption('--enable-fpm');

            if ($bin = Utils::findBin('systemctl') && Utils::findIncludePrefix('systemd/sd-daemon.h')) {
                $params = $params->withOption('--with-fpm-systemd');
            }

            return $params;
        };

        $this->variants['dtrace'] = function (ConfigureParameters $params) {
            return $params->withOption('--enable-dtrace');
        };

        $this->variants['pcre'] = function (ConfigureParameters $params, Build $build, $value) {
            // Apple Silicon will crash on < 8.1.11 due to the bundled PCRE2 not being compatible with Apple Sillicon, so get an updated version if we can
            // PHP 8.1.11 and above have the fix applied to its bundled PCRE2: https://github.com/php/php-src/commit/f8b217a3452e76113b833eec8a49bc2b6e8d1fdd
            if ($build->compareVersion('8.0') >= 0 && $build->compareVersion('8.1.11') < 0 && $build->osName === 'Darwin' && $build->osArch === 'arm64') {
                $prefix = Utils::findPrefix([
                    new UserProvidedPrefix($value),
                    new IncludePrefixFinder('pcre2.h'),
                    new BrewPrefixFinder('pcre2'),
                ]);

                if ($prefix === null) {
                    throw new Exception('Unable to find PCRE2 library. PHP 8.0 on Apple Silicon requires a newer version of PCRE2 than is bundled with PHP 8.0.');
                }

                $params = $params->withOption('--with-external-pcre', $prefix);
                return $params;
            }

            // PCRE is bundled with PHP since 7.4
            if ($build->compareVersion('7.4') >= 0) {
                return $params;
            }

            $params = $params->withOption('--with-pcre-regex');

            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('pcre.h'),
                new BrewPrefixFinder('pcre'),
            ));

            if ($prefix !== null) {
                $params = $params->withOption('--with-pcre-dir', $prefix);
            }

            return $params;
        };

        $this->variants['mhash'] = function (ConfigureParameters $params, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('mhash.h'),
                new BrewPrefixFinder('mhash'),
            ));

            return $params->withOption('--with-mhash', $prefix);
        };

        $this->variants['mcrypt'] = function (ConfigureParameters $params, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('mcrypt.h'),
                new BrewPrefixFinder('mcrypt'),
            ));

            return $params->withOption('--with-mcrypt', $prefix);
        };

        $this->variants['zlib'] = function (ConfigureParameters $params, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('zlib'),
                new IncludePrefixFinder('zlib.h'),
            ));

            return $params->withOptionOrPkgConfigPath($build, '--with-zlib', $prefix);
        };

        $this->variants['curl'] = function (ConfigureParameters $params, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('curl'),
                new PkgConfigPrefixFinder('libcurl'),
                new IncludePrefixFinder('curl/curl.h'),
            ));

            return $params->withOptionOrPkgConfigPath($build, '--with-curl', $prefix);
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
        $this->variants['readline'] = function (ConfigureParameters $params, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('readline'),
                new IncludePrefixFinder('readline/readline.h'),
            ));

            return $params->withOption('--with-readline', $prefix);
        };

        /*
         * editline is conflict with readline
         *
         * one must tap the homebrew/dupes to use this formula
         *
         *      brew tap homebrew/dupes
         */
        $this->variants['editline'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('editline/readline.h'),
                new BrewPrefixFinder('libedit'),
            ));

            return $parameters->withOption('--with-libedit', $prefix);
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
        $this->variants['gd'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('gd.h'),
                new BrewPrefixFinder('gd'),
            ));

            if ($build->compareVersion('7.4') < 0) {
                $option = '--with-gd';
            } else {
                $option = '--enable-gd';
            }

            $value = 'shared';

            if ($prefix !== null) {
                $value .= ',' . $prefix;
            }

            $parameters = $parameters->withOption($option, $value);

            if ($build->compareVersion('5.5') < 0) {
                $parameters = $parameters->withOption('--enable-gd-native-ttf');
            }

            if (
                ($prefix = Utils::findPrefix(array(
                new IncludePrefixFinder('jpeglib.h'),
                new BrewPrefixFinder('libjpeg'),
                ))) !== null
            ) {
                if ($build->compareVersion('7.4') < 0) {
                    $option = '--with-jpeg-dir';
                } else {
                    $option = '--with-jpeg';
                }

                $parameters = $parameters->withOption($option, $prefix);
            }

            if (
                $build->compareVersion('7.4') < 0 && ($prefix = Utils::findPrefix(array(
                new IncludePrefixFinder('png.h'),
                new IncludePrefixFinder('libpng12/pngconf.h'),
                new BrewPrefixFinder('libpng'),
                ))) !== null
            ) {
                $parameters = $parameters->withOption('--with-png-dir', $prefix);
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
                    $option = '--with-freetype-dir';
                } else {
                    $option = '--with-freetype';
                }

                $parameters = $parameters->withOption($option, $prefix);
            }

            return $parameters;
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
        $this->variants['intl'] = function (ConfigureParameters $parameters, Build $build) {
            $parameters = $parameters->withOption('--enable-intl');

            $prefix = Utils::findPrefix(array(
                new PkgConfigPrefixFinder('icu-i18n'),
                new BrewPrefixFinder('icu4c'),
            ));

            if ($build->compareVersion('7.4') < 0) {
                if ($prefix !== null) {
                    $parameters = $parameters->withOption('--with-icu-dir', $prefix);
                }
            } else {
                if ($prefix !== null) {
                    $parameters = $parameters->withPkgConfigPath($prefix . '/lib/pkgconfig');
                }
            }

            return $parameters;
        };

        $this->variants['sodium'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('libsodium'),
                new PkgConfigPrefixFinder('libsodium'),
                new IncludePrefixFinder('sodium.h'),
                new LibPrefixFinder('libsodium.a'),
            ));

            return $parameters->withOption('--with-sodium', $prefix);
        };

        /*
         * --with-openssl option
         *
         * --with-openssh=shared
         * --with-openssl=[dir]
         *
         * On ubuntu you need to install libssl-dev
         */
        $this->variants['openssl'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('openssl'),
                new PkgConfigPrefixFinder('openssl'),
                new IncludePrefixFinder('openssl/opensslv.h'),
            ));

            return $parameters->withOptionOrPkgConfigPath($build, '--with-openssl', $prefix);
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
        $this->variants['mysql'] = function (ConfigureParameters $parameters, Build $build, $value) {
            if ($value === null) {
                $value = 'mysqlnd';
            }

            if ($build->compareVersion('7.0') < 0) {
                $parameters = $parameters->withOption('--with-mysql', $value);
            }

            $parameters = $parameters->withOption('--with-mysqli', $value);

            if ($build->isEnabledVariant('pdo')) {
                $parameters = $parameters->withOption('--with-pdo-mysql', $value);
            }

            $foundSock = false;
            if ($bin = Utils::findBin('mysql_config')) {
                if ($output = exec_line("$bin --socket")) {
                    $foundSock = true;
                    $parameters = $parameters->withOption('--with-mysql-sock', $output);
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
                        $parameters = $parameters->withOption('--with-mysql-sock', $path);
                        break;
                    }
                }
            }

            return $parameters;
        };

        $this->variants['sqlite'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $parameters = $parameters->withOption('--with-sqlite3', $value);

            if ($build->isEnabledVariant('pdo')) {
                $parameters = $parameters->withOption('--with-pdo-sqlite', $value);
            }

            return $parameters;
        };

        /**
         * The --with-pgsql=[DIR] and --with-pdo-pgsql=[DIR] requires [DIR]/bin/pg_config to be found.
         */
        $this->variants['pgsql'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new ExecutablePrefixFinder('pg_config'),
                new BrewPrefixFinder('libpq'),
            ));

            $parameters = $parameters->withOption('--with-pgsql', $prefix);

            if ($build->isEnabledVariant('pdo')) {
                $parameters = $parameters->withOption('--with-pdo-pgsql', $prefix);
            }

            return $parameters;
        };

        $this->variants['xml'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $parameters = $parameters->withOption('--enable-dom');

            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('libxml2'),
                new PkgConfigPrefixFinder('libxml'),
                new IncludePrefixFinder('libxml2/libxml/globals.h'),
                new LibPrefixFinder('libxml2.a'),
            ));

            if ($build->compareVersion('7.4') < 0) {
                $parameters = $parameters->withOption('--enable-libxml');

                if ($prefix !== null) {
                    $parameters = $parameters->withOption('--with-libxml-dir', $prefix);
                }
            } else {
                $parameters = $parameters->withOption('--with-libxml');

                if ($prefix !== null) {
                    $parameters = $parameters->withPkgConfigPath($prefix . '/lib/pkgconfig');
                }
            }

            return $parameters
                ->withOption('--enable-simplexml')
                ->withOption('--enable-xml')
                ->withOption('--enable-xmlreader')
                ->withOption('--enable-xmlwriter')
                ->withOption('--with-xsl');
        };

        $this->variants['apxs2'] = function (ConfigureParameters $parameters, Build $build, $value) {
            if ($value) {
                return $parameters->withOption('--with-apxs2', $value);
            }

            if ($bin = Utils::findBinByPrefix('apxs2')) {
                return $parameters->withOption('--with-apxs2', $bin);
            }

            if ($bin = Utils::findBinByPrefix('apxs')) {
                return $parameters->withOption('--with-apxs2', $bin);
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

            $path = first_existing_executable($possiblePaths);

            return $parameters->withOption('--with-apxs2', $path);
        };

        $this->variants['gettext'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('libintl.h'),
                new BrewPrefixFinder('gettext'),
            ));

            return $parameters->withOption('--with-gettext', $prefix);
        };

        $this->variants['iconv'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                // PHP can't be compiled with --with-iconv=/usr because it uses giconv
                // https://bugs.php.net/bug.php?id=48451
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('libiconv'),
            ));

            return $parameters->withOption('--with-iconv', $prefix);
        };

        $this->variants['bz2'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('bzip2'),
                new IncludePrefixFinder('bzlib.h'),
            ));

            return $parameters->withOption('--with-bz2', $prefix);
        };

        $this->variants['ipc'] = function (ConfigureParameters $parameters, Build $build) {
            return $parameters
                ->withOption('--enable-shmop')
                ->withOption('--enable-sysvsem')
                ->withOption('--enable-sysvshm')
                ->withOption('--enable-sysvmsg');
        };

        $this->variants['gmp'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new IncludePrefixFinder('gmp.h'),
            ));

            return $parameters->withOption('--with-gmp', $prefix);
        };

        $this->variants['pear'] = function (ConfigureParameters $parameters, Build $build, $value) {
            if ($value === null) {
                $value = $build->getInstallPrefix() . '/lib/php/pear';
            }

            return $parameters->withOption('--with-pear', $value);
        };

        /*
         * --with-snmp option
         *
         * --with-snmp=[dir]
         *
         * On macOS, you need to use the brew to install the net-snmp
         *
         * On ubuntu you need to install libsnmp-dev
         * On Ubuntu 18.04+, it should ensure the /usr/include/net-snmp/net-snmp-config.h is available.
         * On Ubuntu 22.04+, it should ensure the pkg-config --variable=prefix netsnmp can find the net-snmp prefix.
         */
        $this->variants['snmp'] = function (ConfigureParameters $parameters, Build $build, $value) {
            $prefix = Utils::findPrefix(array(
                new UserProvidedPrefix($value),
                new BrewPrefixFinder('net-snmp'),
                new PkgConfigPrefixFinder('netsnmp'),
                new IncludePrefixFinder('net-snmp/net-snmp-config.h'),
            ));

            return $parameters->withOptionOrPkgConfigPath($build, '--with-snmp', $prefix);
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

    private function checkConflicts(Build $build)
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

                echo implode(PHP_EOL, $msgs) . PHP_EOL;
            }
        }

        return true;
    }

    public function getVariantNames()
    {
        return array_keys($this->variants);
    }

    /**
     * Build `./configure' parameters from an enabled variant.
     *
     * @param string      $variant Variant name
     * @param string|null $value   User-provided value for the variant
     *
     * @return ConfigureParameters
     *
     * @throws Exception
     */
    private function buildEnabledVariant(Build $build, $variant, $value, ConfigureParameters $parameters)
    {
        if (!isset($this->variants[$variant])) {
            throw new Exception(sprintf('Variant "%s" is not defined', $variant));
        }

        // Skip if we've built it
        if (in_array($variant, $this->builtList)) {
            return $parameters;
        }

        // Skip if we've disabled it
        if (isset($this->disables[$variant])) {
            return $parameters;
        }

        $this->builtList[] = $variant;

        return $this->buildVariantFromDefinition($build, $this->variants[$variant], $value, $parameters);
    }

    /**
     * Build `./configure' parameters from a disabled variant.
     *
     * @param string $variant Variant name
     *
     * @return ConfigureParameters
     *
     * @throws Exception
     */
    private function buildDisabledVariant(Build $build, $variant, ConfigureParameters $parameters)
    {
        if (!isset($this->variants[$variant])) {
            throw new Exception(sprintf('Variant "%s" is not defined', $variant));
        }

        // Skip if we've built it
        if (in_array('-' . $variant, $this->builtList)) {
            return $parameters;
        }

        $this->builtList[] = '-' . $variant;

        $disabledParameters = $this->buildVariantFromDefinition(
            $build,
            $this->variants[$variant],
            null,
            new ConfigureParameters()
        );

        foreach ($disabledParameters->getOptions() as $option => $_) {
            // convert --enable-xxx to --disable-xxx
            $option = preg_replace('/^--enable-/', '--disable-', $option);

            // convert --with-xxx to --without-xxx
            $option = preg_replace('/^--with-/', '--without-', $option);

            $parameters = $parameters->withOption($option);
        }

        return $parameters;
    }

    /**
     * Build `./configure' parameters from a variant definition.
     *
     * @param string|array<string>|callable $definition Variant definition
     * @param string|null                   $value      User-provided value for the variant
     *
     * @return ConfigureParameters
     *
     * @throws Exception
     */
    private function buildVariantFromDefinition(Build $build, $definition, $value, ConfigureParameters $parameters)
    {
        if (is_string($definition)) {
            $parameters = $parameters->withOption($definition);
        } elseif (is_array($definition)) {
            foreach ($definition as $option => $value) {
                $parameters = $parameters->withOption($option, $value);
            }
        } elseif (is_callable($definition)) {
            $parameters = call_user_func_array($definition, array(
                $parameters,
                $build,
                $value,
            ));
        } else {
            throw new OopsException();
        }

        return $parameters;
    }

    /**
     * Build variants to configure options from php build object.
     *
     * @param Build $build The build object, contains version information
     *
     * @return ConfigureParameters
     *
     * @throws Exception
     */
    public function build(Build $build, ConfigureParameters $parameters = null)
    {
        $customVirtualVariants = Config::getConfigParam('variants');
        foreach (array_keys($build->getEnabledVariants()) as $variantName) {
            if (isset($customVirtualVariants[$variantName])) {
                foreach ($customVirtualVariants[$variantName] as $lib => $params) {
                    if (is_array($params)) {
                        $this->variants[$lib] = $params;
                    }
                }
            }
        }

        if ($parameters === null) {
            $parameters = new ConfigureParameters();
        }

        // reset builtList
        $this->builtList = array();

        // reset built options
        if (!$build->isEnabledVariant('all') && !$build->isEnabledVariant('neutral')) {
            // build common options
            $parameters = $parameters
                ->withOption('--disable-all')
                ->withOption('--enable-phar')
                ->withOption('--enable-session')
                ->withOption('--enable-short-tags')
                ->withOption('--enable-tokenizer');

            if ($build->compareVersion('7.4') < 0) {
                $parameters = $parameters->withOption('--with-pcre-regex');
            }

            if ($value = Utils::findIncludePrefix('zlib.h')) {
                $parameters = $parameters->withOption('--with-zlib', $value);
            }
        }

        if ($value = Utils::findLibPrefix('x86_64-linux-gnu')) {
            $parameters = $parameters->withOption('--with-libdir', 'lib/x86_64-linux-gnu');
        } elseif ($value = Utils::findLibPrefix('i386-linux-gnu')) {
            $parameters = $parameters->withOption('--with-libdir', 'lib/i386-linux-gnu');
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

        foreach ($build->getEnabledVariants() as $variant => $value) {
            $parameters = $this->buildEnabledVariant($build, $variant, $value, $parameters);
        }

        foreach ($build->getDisabledVariants() as $variant => $_) {
            $parameters = $this->buildDisabledVariant($build, $variant, $parameters);
        }

        foreach ($build->getExtraOptions() as $option) {
            $parameters = $parameters->withOption($option);
        }

        return $parameters;
    }
}

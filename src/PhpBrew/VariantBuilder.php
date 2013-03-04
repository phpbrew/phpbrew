<?php
namespace PhpBrew;
use PhpBrew\Utils;
use Exception;
use PhpBrew\Build;


/**
 * $variantBuilder = new VariantBuilder;
 * $variantBuilder->register('debug', function() {
 * 
 * });
 * $variantBuilder->build($build);
 */
class VariantBuilder
{

    /**
     * target php version
     */
    public $version;

    /**
     * available variants
     */
    public $variants = array();

    public $conflicts = array(
        // PHP Version lower than 5.4.0 can only built one SAPI at the same time.
        'apxs2' => array( 'fpm','cgi' ),
    );

    public $options = array();


    /**
     * @var array $builtList is for checking built variants
     *
     * contains ['-pdo','mysql','-sqlite','-debug']
     */
    public $builtList = array();

    public $virtualVariants = array(
        'dbs'
    );


    public function __construct()
    {
        $self = $this;

        $this->variants['default'] = function() use($self) {
            $vs = array(
                'filter',
                'bcmath',
                'ctype',
                'fileinfo',
                'pdo',
                'posix',
                'ipc',
                'pcntl',
                'bz2',
                'zip',
                'cli',
                'calendar',
                'sockets',
                'readline',
            );
            $options = array();
            foreach( $vs as $v ) {
                $options = array_merge(
                    $options, $self->buildVariant($v) 
                );
            }
            return $options;
        };

        $this->variants['dbs'] = function() use($self) {
            $options = array();
            foreach( array('sqlite','mysql','pgsql','pdo') as $v ) {
                $options = array_merge(
                    $options,
                    $self->buildVariant($v) );
                $self->enable($v); // enable it
            }
            return $options;
        };

        $this->variants['dba'] = function() {
            return '--enable-dba';
        };

        $this->variants['ipv6'] = function() {
            return '--enable-ipv6';
        };

        $this->variants['all'] = function() {
            return '--enable-all';
        };

        $this->variants['calendar'] = function() {
            return '--enable-calendar';
        };

        $this->variants['zlib'] = function() {
            if( $prefix = Utils::find_include_prefix('zlib.h') ) {
                return '--with-zlib=' . $prefix;
            }
        };

        $this->variants['posix'] = function() {
            return '--enable-posix';
        };

        $this->variants['embed'] = function() {
            return '--enable-embed';
        };

        $this->variants['readline'] = function() {
            $opts = array();
            if( $prefix = Utils::find_include_prefix( 'readline' . DIRECTORY_SEPARATOR . 'readline.h') ) {
                $opts[] = '--with-readline=' . $prefix;
            }

            if( $prefix = Utils::find_include_prefix('editline' . DIRECTORY_SEPARATOR . 'readline.h') ) {
                $opts[] = '--with-libedit=' . $prefix;
            }
            return $opts;
        };

        // init variant builders
        $this->variants['pdo'] = function() use ($self) {
            return '--enable-pdo';
        };

        $this->variants['gd'] = function() use($self) {
            $opts = array();
            if( $prefix = Utils::find_include_prefix('gd.h') ) {
                $opts[] = '--with-gd=' . $prefix;
                $opts[] = '--enable-gd-native-ttf';
            }
            else {
                echo "** libgd not found.\n";
            }

            if( $p = Utils::find_include_prefix('jpeglib.h') ) {
                $opts[] = '--with-jpeg-dir=' . $p;
            }

            if( $p = Utils::find_include_prefix('png.h') ) {
                $opts[] = '--with-png-dir=' . $p;
            }
            return $opts;
        };


        /**
         * with icu
         */
        $this->variants['icu'] = function($val = null) use($self) {
            // XXX: it seems that /usr prefix does not work on Ubuntu 
            //       Linux system.
            if( $val ) {
                return '--with-icu=' . $val;
            }
            $prefix = Utils::get_pkgconfig_prefix('icu-i18n');
            if( ! $prefix ) {
                echo "phpbrew precheck: icu not found.\n";
                return '--with-icu';
            }
            return '--with-icu';
        };


        /**
         * --with-openssl option
         */
        $this->variants['openssl'] = function($val = null) use($self) {
            // XXX: it seems that /usr prefix does not work on Ubuntu Linux system.
            if( $val ) {
                return '--with-openssl=' . $val;
            }
            $prefix = Utils::get_pkgconfig_prefix('openssl');
            if( ! $prefix ) {
                echo "phpbrew precheck: openssl not found.\n";
                return '--with-openssl';
            }
            return '--with-openssl=shared';
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

        --with-mysql         // deprecated
        */
        $this->variants['mysql'] = function( $prefix = 'mysqlnd' ) use ($self) {
            $opts = array(
                "--with-mysql=$prefix",
                "--with-mysqli=$prefix"
            );
            if( isset($self->use['pdo']) )
                $opts[] = "--with-pdo-mysql=$prefix";
            return $opts;
        };

        $this->variants['fpm'] = function() use ($self) {
            return '--enable-fpm';
        };

        $this->variants['sqlite'] = function( $prefix = null ) use ($self) {
            $opts = array(
                '--with-sqlite3' . ($prefix ? "=$prefix" : '')
            );
            if( isset($self->use['pdo']) )
                $opts[] = '--with-pdo-sqlite';
            return $opts;
        };

        $this->variants['pgsql'] = function($prefix = null) use($self) {
            $opts = array();
            $opts[] = '--with-pgsql' . ($prefix ? "=$prefix" : '');
            if( isset($self->use['pdo']) )
                $opts[] = '--with-pdo-pgsql';
            return $opts;
        };

        $this->variants['cli'] = function() {
            return '--enable-cli';
        };

        $this->variants['ftp'] = function() {
            return '--enable-ftp';
        };

        $this->variants['filter'] = function() {
            return '--enable-filter';
        };

        $this->variants['gcov'] = function() {
            return '--enable-gcov';
        };

        $this->variants['sockets'] = function() {
            return '--enable-sockets';
        };

        $this->variants['apxs2'] = function($prefix = null) use ($self) {

            $a = '--with-apxs2';
            $apxs = null;
            if( $prefix ) {
                $a .= '=' . $prefix;
                $apxs = $prefix;
            }

            if( ! $apxs ) {
                $apxs = Utils::findbin('apxs');
            }

            // use apxs to check module dir permission
            if( $apxs && $libdir = trim( Utils::pipe_execute( "$apxs -q LIBEXECDIR" ) ) ) {
                if( false === is_writable($libdir) ) {
                    $msg = array();
                    throw new Exception("Apache module dir $libdir is not writable.\nPlease consider using chmod or sudo.");
                }
            }
            if( $apxs && $confdir = trim( Utils::pipe_execute( "$apxs -q SYSCONFDIR" ) ) ) {
                if( false === is_writable($confdir) ) {
                    $msg = array();
                    $msg[] = "Apache conf dir $confdir is not writable for phpbrew.";
                    $msg[] = "Please consider using chmod or sudo: ";
                    $msg[] = "    \$ sudo chmod -R og+rw $confdir";
                    throw new Exception( join("\n", $msg ) );
                }
            }
            return $a;
        };

        $this->variants['debug'] = function() {
            return array('--enable-debug');
        };

        $this->variants['zip'] = function() {
            return '--enable-zip';
        };

        $this->variants['bcmath'] = function() {
            return '--enable-bcmath';
        };

        $this->variants['fileinfo'] = function() {
            return '--enable-fileinfo';
        };

        $this->variants['ctype'] = function() {
            return '--enable-ctype';
        };

        $this->variants['cgi'] = function() {
            return '--enable-cgi';
        };

        $this->variants['gettext'] = function($prefix = null) {
            if( $prefix )
                return '--with-gettext=' . $prefix;
            if( $prefix = Utils::find_include_prefix('libintl.h') )
                return '--with-gettext=' . $prefix;
            return '--with-gettext';
        };

        $this->variants['soap'] = function() {
            return '--enable-soap';
        };

        $this->variants['pcntl'] = function() {
            return '--enable-pcntl';
        };

        $this->variants['intl'] = function() {
            return '--enable-intl';
        };

        $this->variants['imap'] = function() {
            return '--with-imap-ssl';
        };

        $this->variants['tidy'] = function() {
            return '--with-tidy';
        };

        $this->variants['kerberos'] = function() {
            return '--with-kerberos';
        };

        $this->variants['iconv'] = function() {
            // detect include path for iconv.h
            if( $prefix = Utils::find_include_prefix('iconv.h') ) {
                return "--with-iconv";
                // return "--with-iconv=$prefix";
            }
        };

        $this->variants['bz2'] = function($prefix = null) {
            if( ! $prefix && $prefix = Utils::find_include_prefix('bzlib.h') ) {
                    return '--with-bz2=' . $prefix;
            }
        };

        $this->variants['ipc'] = function() {
            return array(
                '--enable-shmop',
                '--enable-sysvsem',
                '--enable-sysvshm',
                '--enable-sysvmsg',
            );
        };

        // '--with-mcrypt=/usr',
    }

    /*
    private function _getConflict($feature)
    {
        if( isset( $this->conflicts[ $feature ] ) ) {
            $conflicts = array();
            foreach( $this->conflicts[ $feature ] as $f ) {
                if( $this->isUsing($f) )
                    $conflicts[] = $f;
            }
            return $conflicts;
        }
        return false;
    }
     */


/*
    public function checkConflicts()
    {
        if( isset($this->use['apxs2'])
            && version_compare( $this->version , 'php-5.4.0' ) < 0 )
        {
            if( $conflicts = $this->_getConflict('apxs2') ) {
                $msgs = array();
                $msgs[] = "PHP Version lower than 5.4.0 can only build one SAPI at the same time.";
                $msgs[] = "+apxs2 is in conflict with " . join(',',$conflicts);
                foreach( $conflicts as $c ) {
                    $msgs[] = "Disabling $c";
                    $this->disable($c);
                }
                echo join("\n",$msgs) . "\n";
            }
        }
        return true;
    }
*/

    public function checkPkgPrefix($option,$pkgName)
    {
        $prefix = Utils::get_pkgconfig_prefix($pkgName);
        return $prefix ? $option . '=' . $prefix : $option;
    }

    public function getVariantNames()
    {
        return array_keys( $this->variants );
    }



    /**
     * Build options from variant
     *
     * @param string $feature variant name
     * @param string $userValue option value.
     */
    public function buildVariant($feature,$userValue = null)
    {
        if( isset( $this->variants[ $feature ] )) {
            // Skip if we've built it
            if ( in_array($feature, $this->builtList) ) 
                return array();

            // Skip if we've disabled it
            if ( isset($this->disables[ $feature ] )) 
                return array();

            $this->builtList[] = $feature;
            $func = $this->variants[ $feature ];
            $args = array();
            if( is_string($userValue) )
                $args[] = $userValue;
            return (array) call_user_func_array($func,$args);
        }
        else {
            throw new Exception("Variant $feature is not defined.");
        }
    }

    public function buildDisableVariant($feature,$userValue = null)
    {
        if( isset( $this->variants[ $feature ] )) {
            if ( in_array('-'.$feature, $this->builtList) ) return array();
            $this->builtList[] = '-'.$feature;
            $func = $this->variants[ $feature ];
            $args = array();
            if( is_string($userValue) )
                $args[] = $userValue;
            $disableOptions = (array) call_user_func_array($func,$args);
            $resultOptions = array();

            foreach($disableOptions as $option) {
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
        else {
            throw new Exception("Variant $feature is not defined.");
        }
    }

    /**
     * build configure options from variants
     */
    public function build($build)
    {

        // build common options
        $options = array(
            '--disable-all',
            '--enable-dom',
            '--enable-exif',
            '--enable-hash',
            '--enable-json',
            '--enable-mbregex',
            '--enable-mbstring',
            '--enable-phar',
            '--enable-session',
            '--enable-short-tags',
            '--enable-libxml',
            '--enable-simplexml',
            '--enable-tokenizer',
            '--enable-xml',
            '--enable-xmlreader',
            '--enable-xmlwriter',
            '--with-xsl',
            '--with-xmlrpc',
            '--with-mhash',
            '--with-pcre-regex',
        );


        // reset builtList
        $this->builtList = array();

        if( $prefix = Utils::find_include_prefix('zlib.h') ) {
            $options[] = '--with-zlib=' . $prefix;
        }


        if( $prefix = Utils::get_pkgconfig_prefix('libxml') ) {
            $options[] = '--with-libxml-dir=' . $prefix;
        }

        if( $prefix = Utils::get_pkgconfig_prefix('libcurl') ) {
            $options[] = '--with-curl=' . $prefix;
        }

        // $this->checkConflicts();


        if( $defOptions = $this->buildVariant('default') ) {
            $options = array_merge($options, $defOptions );
        }

        // virtual variants first
        foreach( $this->virtualVariants as $virtualVariant) {
            if (array_key_exists($virtualVariant, $this->use)){
                if( $vOptions = $this->buildVariant($virtualVariant) ) {
                    $options = array_merge($options, $vOptions );
                }
            }
        }


        // build variant options from enabled variants 
        foreach( $this->use as $feature => $userValue ) {
            if( $feature == 'default' || in_array($feature, $this->virtualVariants) )
                continue;
            if( $vOptions = $this->buildVariant( $feature , $userValue ) ) {
                $options = array_merge($options, $vOptions );
            }
        }

        // build disabled options from disabled variants
        foreach( $this->disables as $feature => $userValue ) {
            if( $feature == 'default' || in_array($feature, $this->virtualVariants) )
                continue;
            if( $options = $this->buildDisableVariant( $feature ) ) {
                $this->options = array_merge($this->options, $options );
            }
        }


        /*
        $opts = array_merge( $opts ,
            $this->getVersionOptions($version) );
        */
        return $this->options;
    }





    /**
     * get available variants for $version
     *
     * @param string $version version string
     */
    /*
    public function getAvailableVariants($version)
    {
        // xxx: use version_compare to merge config options


        if( isset($this->variants[$version]) )
            return $this->variants;
        // try to match regular expressions
        foreach( $this->variants as $k => $variants ) {
            if( strpos($k,'/') === 0 ) {
                if( preg_match( $k , $version ) )
                    return $variants;
            }
        }
    }
    */



    /*
    public function getVersionOptions($version)
    {
        $options = array();
        $defs = array();


        $defs['= php-5.2'] = array();
        $defs['= php-5.3'] = array();
        $defs['= php-5.4.0RC7'] = array();


        foreach($defs as $versionExp => $versionOptions ) {
            if( preg_match('/^([=<>]+)\s+(\S+)$/',$versionExp,$regs) ) {
                list($orig,$op,$rVersion) = $regs;

                switch($op)
                {
                    case '=':
                        if( version_compare($version,$rVersion) === 0 ) {
                            $options = array_merge( $options, $versionOptions );
                        }
                        break;
                    case '>':
                        if( version_compare($version,$rVersion) > 0 ) {

                        }
                        break;
                    case '<':
                        if( version_compare($version,$rVersion) < 0 ) {

                        }
                        break;
                }
            }
            else {
                throw new Exception("Unsupported format $versionExp");
            }
        }
        return $options;
    }
    */
}


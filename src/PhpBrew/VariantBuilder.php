<?php
namespace PhpBrew;
use PhpBrew\Utils;
use Exception;
use PhpBrew\Build;
use PhpBrew\Exceptions\OopsException;


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

        // provide all basic features
        'default' => array(
            'filter',
            'dom',
            'bcmath',
            'ctype',
            'mhash',
            'fileinfo',
            'pdo',
            'posix',
            'ipc',
            'pcntl',
            'bz2',
            'zip',
            'cli',
            'json',
            'mbstring',
            'mbregex',
            'calendar',
            'sockets',
            'readline',
            'xml_all',
        )
    );


    public function __construct()
    {
        $self = $this;

        // init variant builders

        $this->variants['all']      = '--enable-all';
        $this->variants['dba']      = '--enable-dba';
        $this->variants['ipv6']     = '--enable-ipv6';
        $this->variants['dom']      = '--enable-dom';
        $this->variants['all']      = '--enable-all';
        $this->variants['calendar'] = '--enable-calendar';

        $this->variants['cli']      = '--enable-cli';
        $this->variants['fpm']      = '--enable-fpm';
        $this->variants['ftp']      = '--enable-ftp';
        $this->variants['filter']   = '--enable-filter';
        $this->variants['gcov']     = '--enable-gcov';


        $this->variants['json']     = '--enable-json';
        $this->variants['hash']     = '--enable-hash';
        $this->variants['exif']     = '--enable-exif';
        $this->variants['mbstring'] = '--enable-mbstring';
        $this->variants['mbregex']  = '--enable-mbregex';

        $this->variants['pdo']      = '--enable-pdo';
        $this->variants['posix']    = '--enable-posix';
        $this->variants['embed']    = '--enable-embed';
        $this->variants['sockets']  = '--enable-sockets';
        $this->variants['debug']    = '--enable-debug';
        $this->variants['zip']      = '--enable-zip';
        $this->variants['bcmath']   = '--enable-bcmath';
        $this->variants['fileinfo'] = '--enable-fileinfo';
        $this->variants['ctype']    = '--enable-ctype';
        $this->variants['cgi']      = '--enable-cgi';
        $this->variants['soap']     = '--enable-soap';
        $this->variants['pcntl']    = '--enable-pcntl';
        $this->variants['intl']     = '--enable-intl';
        $this->variants['phar']     = '--enable-phar';
        $this->variants['session']     = '--enable-session';
        $this->variants['tokenizer']     = '--enable-tokenizer';

        $this->variants['mhash'] = '--with-mhash';
        $this->variants['mcrypt'] = '--with-mcrypt';
        $this->variants['imap'] = '--with-imap-ssl';
        $this->variants['tidy'] = '--with-tidy';
        $this->variants['kerberos'] = '--with-kerberos';

        $this->variants['zlib'] = function() {
            if( $prefix = Utils::find_include_prefix('zlib.h') ) {
                return '--with-zlib=' . $prefix;
            }
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


        $this->variants['xml_all'] = function() {
            return array(
                '--enable-libxml',
                '--enable-simplexml',
                '--enable-xml',
                '--enable-xmlreader',
                '--enable-xmlwriter',
                '--with-xsl'
            );
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


        $this->variants['gettext'] = function($prefix = null) {
            if( $prefix )
                return '--with-gettext=' . $prefix;
            if( $prefix = Utils::find_include_prefix('libintl.h') )
                return '--with-gettext=' . $prefix;
            return '--with-gettext';
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
    }

    private function _getConflict($build, $feature)
    {
        if( isset( $this->conflicts[ $feature ] ) ) {
            $conflicts = array();
            foreach( $this->conflicts[ $feature ] as $f ) {
                if( $this->isEnabledVariant($f) )
                    $conflicts[] = $f;
            }
            return $conflicts;
        }
        return false;
    }


    public function checkConflicts($build)
    {
        if ( $build->isEnabledVariant('apxs2') && version_compare( $build->getVersion() , 'php-5.4.0' ) < 0 )
        {
            if( $conflicts = $this->_getConflict($build,'apxs2') ) {
                $msgs = array();
                $msgs[] = "PHP Version lower than 5.4.0 can only build one SAPI at the same time.";
                $msgs[] = "+apxs2 is in conflict with " . join(',',$conflicts);
                foreach( $conflicts as $c ) {
                    $msgs[] = "Disabling $c";
                    $build->disableVariant($c);
                }
                echo join("\n",$msgs) . "\n";
            }
        }
        return true;
    }

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
    public function buildVariant($feature, $userValue = null)
    {
        if( ! isset( $this->variants[ $feature ] )) {
            throw new Exception("Variant '$feature' is not defined.");
        }

        // Skip if we've built it
        if ( in_array($feature, $this->builtList) ) 
            return array();

        // Skip if we've disabled it
        if ( isset($this->disables[ $feature ] )) 
            return array();

        $this->builtList[] = $feature;
        $cb = $this->variants[ $feature ];

        if ( is_array($cb) ) {
            return $cb;
        } elseif ( is_string($cb) ) {
            return array($cb);
        } elseif( is_callable($cb) ) {
            $args = is_string($userValue) ? array($userValue) : array();
            return (array) call_user_func_array($cb, $args);
        } else {
            throw OopsException;
        }
    }

    public function buildDisableVariant($feature,$userValue = null)
    {
        if( isset( $this->variants[ $feature ] )) {
            if ( in_array('-'.$feature, $this->builtList) ) 
                return array();

            $this->builtList[] = '-'.$feature;
            $func = $this->variants[ $feature ];


            // build the option from enabled variant, 
            // then convert the '--enable' and '--with' options 
            // to '--disable' and '--without'
            $args = is_string($userValue) ? array($userValue) : array();
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



    public function addOptions($options)
    {
        // skip false value
        if( ! $options ) {
            return;
        }
        if (is_string($options) ) {
            $this->options[] = $options;
        } else {
            $this->options = array_merge($this->options,$options);
        }
    }



    /**
     * build configure options from variants
     */
    public function build($build)
    {
        // reset built options
        // build common options
        $this->options = array(
            '--disable-all',
            '--enable-phar',
            '--enable-session',
            '--enable-short-tags',
            '--enable-tokenizer',

            '--with-xmlrpc',
            '--with-pcre-regex',
        );

        // reset builtList
        $this->builtList = array();


        if( $prefix = Utils::find_include_prefix('zlib.h') ) {
            $this->addOptions('--with-zlib=' . $prefix);
        }


        if( $prefix = Utils::get_pkgconfig_prefix('libxml') ) {
            $this->addOptions('--with-libxml-dir=' . $prefix);
        }

        if( $prefix = Utils::get_pkgconfig_prefix('libcurl') ) {
            $this->addOptions('--with-curl=' . $prefix);
        }

        // build virtual variants first
        foreach( $this->virtualVariants as $name => $variantNames ) {
            if( $build->isEnabledVariant($name) ) {
                foreach( $variantNames as $subVariantName ) {
                    $build->enableVariant( $subVariantName );
                }
                // it's a virtual variant, can not be built by buildVariant 
                // method.
                $build->removeVariant( $name );
            }
        }

        // Remove these enabled variant for disabled variants.
        $build->resolveVariants();


        // before we build these options from variants,
        // we need to check the enabled and disabled variants
        $this->checkConflicts($build);


        foreach( $build->getVariants() as $feature => $userValue ) {
            if( $options = $this->buildVariant( $feature , $userValue ) ) {
                $this->addOptions($options);
            }
        }

        foreach( $build->getDisabledVariants() as $feature => $true ) {
            if( $options = $this->buildDisableVariant( $feature ) ) {
                $this->addOptions($options);
            }
        }

        /*
        $opts = array_merge( $opts ,
            $this->getVersionSpecificOptions($version) );
        */
        $options =  $this->options;

        // reset options
        $this->options = array();
        return $options;
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
    public function getVersionSpecificOptions($version)
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


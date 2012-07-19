<?php
namespace PhpBrew;
use PhpBrew\Utils;
use Exception;


/**

$variants = new Variants;
$variants->add('mysql');
$variants->add('pdo', '/custom/prefix');
$variants->build( );

*/
class Variants
{

    /**
     * target php version
     */
    public $version;

    /**
     * available variants 
     */
    public $variants = array();

    /**
     * used features
     */
    public $use = array();


    /**
     * default use variants
     */
    public $defaultUse = array(
        'pdo' => 1,
        'bz2' => 1,
        'cli' => 1,
        'fpm' => 1,
        'bz2' => 1,
        'posix' => 1,
        'calendar' => 1,
        'sockets' => 1,
        'readline' => 1,
    );

    public $disables = array();

    public $conflicts = array(
        // PHP Version lower than 5.4.0 can only built one SAPI at the same time.
        'apxs2' => array( 'fpm','cgi' ),
    );

    public function __construct()
    {
        $self = $this;

        $this->variants['calendar'] = function() {
            return '--enable-calendar';
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
        $this->variants['pdo'] = function() {
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
         * For --with-openssl option
         *
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
            if( isset($self->use['pdo']) ) {
                $opts[] = "--with-pdo-mysql=$prefix";
            }
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

        $this->variants['cgi'] = function() {
            return '--enable-cgi';
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
            if( ! $prefix 
                && $prefix = Utils::find_include_prefix('bzlib.h') ) {
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

        /**
         * default features 
         **/
        foreach( $this->defaultUse as $u => $v ) {
            $this->enable($u);
        }
    }

    public function isDefault($feature)
    {
        return isset($this->defaultUse[$feature]);
    }


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
                    unset($this->use[$c]);
                }
                $this->disables[] = '--disable-fpm';
                $this->disables[] = '--disable-cgi';
                // $this->disables[] = '--disable-cli';
                echo join("\n",$msgs) . "\n";
            }
        }
        return true;
    }

    public function enable($feature,$value = true )
    {
        $this->use[ $feature ] = $value;
    }

    public function isUsing($feature)
    {
        return isset( $this->use[ $feature ] );
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

    public function buildVariant($feature,$userValue = null)
    {
        if( isset( $this->variants[ $feature ] ) ) {
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


    /**
     * build configure options
     */
    public function build()
    {
        return $this->buildOptions();
    }

    public function buildOptions()
    {

        // build common options
        $opts = array(
            '--disable-all',
            '--enable-bcmath',
            '--enable-ctype',
            '--enable-dom',
            '--enable-exif',
            '--enable-fileinfo',
            '--enable-filter',
            '--enable-hash',
            '--enable-json',
            '--enable-libxml',
            '--enable-mbregex',
            '--enable-mbstring',
            '--enable-phar',
            '--enable-session',
            '--enable-short-tags',
            '--enable-simplexml',
            '--enable-sockets',
            '--enable-tokenizer',
            '--enable-xml',
            '--enable-xmlreader',
            '--enable-xmlwriter',
            '--enable-zip',

            '--with-xsl',
            '--with-tidy',
            '--with-xmlrpc',
            '--with-mhash',
            '--with-pcre-regex',
        );

        if( $prefix = Utils::find_include_prefix('zlib.h') ) {
            $opts[] = '--with-zlib=' . $prefix;
        }


        if( $prefix = Utils::get_pkgconfig_prefix('libxml') ) {
            $opts[] = '--with-libxml-dir=' . $prefix;
        }

        if( $prefix = Utils::get_pkgconfig_prefix('libcurl') ) {
            $opts[] = '--with-curl=' . $prefix;
        }

        if( $prefix = Utils::find_include_prefix('libintl.h') ) {
            $opts[] = '--with-gettext=' . $prefix;
        }

        $this->checkConflicts();

        foreach( $this->use as $feature => $userValue ) {
            if( $options = $this->buildVariant( $feature , $userValue ) ) {
                $opts = array_merge( $opts, $options );
            }
        }

        foreach( $this->disables as $d ) {
            $opts[] = $d;
        }

        /*
        $opts = array_merge( $opts , 
            $this->getVersionOptions($version) );
        */
        return $opts;
    }





    /**
     * get available variants for $version
     *
     * @param string $version version string
     */
    public function getAvailableVariants($version)
    {
        // xxx: use version_compare to merge config options


        if( isset($this->variants[$version]) ) 
            return $this->variants;

        /** try to match regular expressions */
        foreach( $this->variants as $k => $variants ) {
            if( strpos($k,'/') === 0 ) {
                if( preg_match( $k , $version ) ) 
                    return $variants;
            }
        }
    }


    public function getVariantOptions($version,$variant)
    {
        $variants = $this->getAvailableVariants($version);
        // todo:
    }


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
}


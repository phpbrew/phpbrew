<?php
namespace PhpBrew;
use PhpBrew\Utils;


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




    public function __construct()
    {
        $self = $this;

        // init variant builders
        $this->variants['pdo'] = function() {
            return '--enable-pdo';
        };

        $this->variants['pear'] = function() {
            return '--with-pear';
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

        $this->variants['sqlite'] = function() use ($self) {
            $opts = array( '--with-sqlite3' );
            if( isset($self->use['pdo']) )
                $opts[] = '--with-pdo-sqlite';
            return $opts;
        };

        $this->variants['cli'] = function() {
            return '--enable-cli';
        };

        $this->variants['apxs2'] = function($prefix = null) {
            $a = '--apxs2';
            if( $prefix ) {
                $a .= '=' . $prefix;
            }
            return array( $a );
        };

        $this->variants['debug'] = function() {
            return array('--enable-debug');
        };

        $this->variants['cgi'] = function() {
            return '--disable-cgi';
        };

        $this->variants['soap'] = function() {
            return '--enable-soap';
        };

        $this->variants['pcntl'] = function() {
            return '--enable-pcntl';
        };

            /*
            '--enable-shmop',
            '--enable-sysvsem',
            '--enable-sysvshm',
            '--enable-sysvmsg',
            '--enable-intl',
            */

            // '--with-imap-ssl',
            // '--with-kerberos',
            // '--with-jpeg-dir=/usr',
            // '--with-png-dir=/usr',
            // '--with-mcrypt=/usr',


    }

    public function useFeature($feature,$value = null)
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
            if( $userValue )
                $args[] = $userValue;
            return (array) call_user_func_array($func,$args);
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
            '--enable-cli',
            '--enable-ctype',
            '--enable-dom',
            '--enable-exif',
            '--enable-fileinfo',
            '--enable-filter',
            '--enable-hash',
            '--with-xsl',
            '--with-tidy',
            '--with-xmlrpc',

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
            '--with-bz2',
            '--with-mhash',
            '--with-pcre-regex',
        );

        $opts[] = $this->checkPkgPrefix('--with-zlib','zlib');
        $opts[] = $this->checkPkgPrefix('--with-libxml-dir','libxml');
        $opts[] = $this->checkPkgPrefix('--with-curl','libcurl');
        $opts[] = $this->checkPkgPrefix('--with-openssl','openssl');

        if( $prefix = Utils::find_include_path('libintl.h') ) {
            $opts[] = '--with-gettext=' . $prefix;
        }

        if( $prefix = Utils::find_include_path('editline' . DIRECTORY_SEPARATOR . 'readline.h') ) {
            $opts[] = '--with-libedit=' . $prefix;
        }


        $opts[] = '--with-readline';

        foreach( $this->use as $feature => $userValue ) {
            $options = $this->buildVariant( $feature , $userValue );
            $opts = array_merge( $opts, $options );
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


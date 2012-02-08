<?php
namespace PhpBrew;
use PhpBrew\PkgConfig;

class Variants
{

    public function __construct()
    {

        /*
        $this->add( '/php-5.4/', array(
            'mysql' => array( 
                    '--with-mysql=mysqlnd',
                    '--with-mysqli=mysqlnd'
                ),
            'sqlite' => array( 
                '--with-sqlite3',
                '--with-pdo-sqlite',
                ),
            'pdo' => array( '--enable-pdo' ),
            'cli' => array( '--enable-cli' ),
        ));
        */
    }


    /**
     * add and merge new config with common variants config
     *
     * @param string $k version string or pattern
     * @param array  $config config options array
     */
    public function add($k,$config)
    {
        $this->variants[ $k ] = $config;
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

    public function checkHeader($hfile)
    {
        $prefixes = array('/usr', '/opt', '/usr/local', '/opt/local' );
        foreach( $prefixes as $prefix ) {
            $p = $prefix . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . $hfile;
            if( file_exists($p) )
                return $prefix;
        }
    }

    public function checkPkgPrefix($option,$pkgName)
    {
        $prefix = PkgConfig::getPrefix($pkgName);
        return $prefix ? $option . '=' . $prefix : $option;
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

    public function getOptions($version)
    {
        $opts = array(
            '--disable-all',
            '--disable-debug',
            '--enable-bcmath',
            '--enable-cli',
            '--enable-ctype',
            '--enable-dom',
            '--enable-exif',
            '--enable-fileinfo',
            '--enable-filter',
            '--enable-hash',
            // '--enable-intl',
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
            '--with-pear',

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
            */

            // '--with-mysql',  // deprecated
            '--enable-pdo',
            '--with-mysql=mysqlnd',
            '--with-mysqli=mysqlnd',
            '--with-pdo-mysql=mysqlnd',

            '--disable-cgi',
            '--enable-shmop',
            '--enable-sysvsem',
            '--enable-sysvshm',
            '--enable-sysvmsg',
            // '--with-imap-ssl',
            // '--with-kerberos',
            // '--enable-soap',
            // '--with-xsl',
            // '--with-tidy',
            // '--with-xmlrpc',
            // '--with-jpeg-dir=/usr',
            // '--with-png-dir=/usr',
            // '--with-mcrypt=/usr',
        );

        $opts[] = $this->checkPkgPrefix('--with-zlib','zlib');
        $opts[] = $this->checkPkgPrefix('--with-libxml-dir','libxml');
        $opts[] = $this->checkPkgPrefix('--with-curl','libcurl');
        $opts[] = $this->checkPkgPrefix('--with-openssl','openssl');

        if( $prefix = $this->checkHeader('libintl.h') ) {
            $opts[] = '--with-gettext=' . $prefix;
        }

        if( $prefix = $this->checkHeader('editline' . DIRECTORY_SEPARATOR . 'readline.h') ) {
            $opts[] = '--with-libedit=' . $prefix;
        }

        $opts[] = '--with-readline';
        $opts = array_merge( $opts , $this->getVersionOptions($version) );

        return $opts;
    }

}


<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription('Show current php information.');
    }

    /* public function header($text)
    {
        $f = $this->logger->formatter;
        echo $f->format( $text . "\n" , 'strong_white' );
    } */

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->header( 'Version' );
        echo "PHP-" , phpversion(), "\n\n";

        $this->header( 'Constants' );
        $consts = get_defined_constants();

        echo "PHP Prefix: " , $consts['PHP_PREFIX'] , "\n";
        echo "PHP Binary: " , $consts['PHP_BINARY'] , "\n";
        echo "PHP Default Include path: " , $consts['DEFAULT_INCLUDE_PATH'] , "\n";
        echo "PHP Include path: ", get_include_path() , "\n";
        echo "\n";

        // DEFAULT_INCLUDE_PATH
        // PEAR_INSTALL_DIR
        // PEAR_EXTENSION_DIR
        // ZEND_THREAD_SAFE
        // zend_version

        $this->header( 'General Info' );
        phpinfo(INFO_GENERAL);
        echo "\n";


        $this->header( 'Extensions' );

        $extensions = get_loaded_extensions();
        $this->logger->info( join( ', ', $extensions ) );

        echo "\n";

        $this->header( 'Database Extensions' );
        foreach( array_filter($extensions, function($n) { return
            in_array($n,array(
                'PDO',
                'pdo_mysql',
                'pdo_pgsql',
                'pdo_sqlite',
                'pgsql',
                'mysqli',
                'mysql',
                'oci8',
                'sqlite3',
                'mysqlnd'
            )); }) as $extname ) {
                $this->logger->info( $extname, 1 );
        }
    }
}




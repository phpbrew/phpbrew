<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\PkgConfig;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('white', null, array('bold'));
        $output->getFormatter()->setStyle('strong_white', $style);

        $output->writeln('<strong_white>Version</strong_white>');

        $output->writeln('PHP-' . phpversion());
        $output->writeln('');

        $output->writeln('<strong_white>Constants</strong_white>');
        $consts = get_defined_constants();

        $output->writeln("PHP Prefix: " . $consts['PHP_PREFIX']);
        $output->writeln("PHP Binary: " . $consts['PHP_BINARY']);
        $output->writeln("PHP Default Include path: " . $consts['DEFAULT_INCLUDE_PATH']);
        $output->writeln("PHP Include path: " . get_include_path());

        // DEFAULT_INCLUDE_PATH
        // PEAR_INSTALL_DIR
        // PEAR_EXTENSION_DIR
        // ZEND_THREAD_SAFE
        // zend_version

        $this->header( 'General Info' );
        phpinfo(INFO_GENERAL);

        $this->header( 'Extensions' );

        $extensions = get_loaded_extensions();
        $this->logger->info( join( ', ', $extensions ) );

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




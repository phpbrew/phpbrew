<?php

namespace PHPBrew\Command;

use CLIFramework\Command;

class InfoCommand extends Command
{
    public function brief()
    {
        return 'Show current php information';
    }

    public function usage()
    {
        return 'phpbrew info';
    }

    public function header($text)
    {
        $f = $this->logger->formatter;
        echo $f->format($text . "\n", 'strong_white');
    }

    public function execute()
    {
        $this->header('Version');
        echo 'PHP-', phpversion(), "\n\n";

        $this->header('Constants');
        $constants = get_defined_constants();

        if (isset($constants['PHP_PREFIX'])) {
            echo 'PHP Prefix: ', $constants['PHP_PREFIX'], "\n";
        }
        if (isset($constants['PHP_BINARY'])) {
            echo 'PHP Binary: ', $constants['PHP_BINARY'], "\n";
        }
        if (isset($constants['DEFAULT_INCLUDE_PATH'])) {
            echo 'PHP Default Include path: ', $constants['DEFAULT_INCLUDE_PATH'], "\n";
        }
        echo 'PHP Include path: ', get_include_path(), "\n";
        echo "\n";

        // DEFAULT_INCLUDE_PATH
        // PEAR_INSTALL_DIR
        // PEAR_EXTENSION_DIR
        // ZEND_THREAD_SAFE
        // zend_version

        $this->header('General Info');
        phpinfo(INFO_GENERAL);
        echo "\n";

        $this->header('Extensions');

        $extensions = get_loaded_extensions();
        $this->logger->info(implode(', ', $extensions));

        echo "\n";

        $this->header('Database Extensions');
        foreach (
            array_filter(
                $extensions,
                function ($n) {
                    return in_array(
                        $n,
                        array(
                        'PDO',
                        'pdo_mysql',
                        'pdo_pgsql',
                        'pdo_sqlite',
                        'pgsql',
                        'mysqli',
                        'mysql',
                        'oci8',
                        'sqlite3',
                        'mysqlnd',
                        )
                    );
                }
            ) as $extName
        ) {
            $this->logger->info($extName, 1);
        }
    }
}

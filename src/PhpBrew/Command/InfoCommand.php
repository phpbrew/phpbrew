<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\UsePhpFunctionWrapper;

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
        UsePhpFunctionWrapper::execute('phpversion()', $output)
            ? $version = $output
            : $version = '';
        echo 'PHP-', $version, "\n\n";

        $this->header('Constants');
        UsePhpFunctionWrapper::execute('get_defined_constants()', $output)
            ? $constants = $output
            : $constants = array();

        if (isset($constants['PHP_PREFIX'])) {
            echo 'PHP Prefix: ', $constants['PHP_PREFIX'], "\n";
        }
        if (isset($constants['PHP_BINARY'])) {
            echo 'PHP Binary: ', $constants['PHP_BINARY'], "\n";
        }
        if (isset($constants['DEFAULT_INCLUDE_PATH'])) {
            echo 'PHP Default Include path: ', $constants['DEFAULT_INCLUDE_PATH'], "\n";
        }
        UsePhpFunctionWrapper::execute('get_include_path()', $output)
            ? $includePath = $output
            : $includePath = '';
        echo 'PHP Include path: ', $includePath, "\n";
        echo "\n";

        // DEFAULT_INCLUDE_PATH
        // PEAR_INSTALL_DIR
        // PEAR_EXTENSION_DIR
        // ZEND_THREAD_SAFE
        // zend_version

        $this->header('General Info');
        UsePhpFunctionWrapper::execute('phpinfo(INFO_GENERAL)', $output, true)
            ? $info = $output
            : $info = '';
        echo $info;
        echo "\n";

        $this->header('Extensions');

        UsePhpFunctionWrapper::execute('get_loaded_extensions()', $output)
            ? $extensions = $output
            : $extensions = array();
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

<?php

namespace PhpBrew\Command;

use CLIFramework\Command;

class InfoCommand extends Command
{
    public function brief()
    {
        return 'Show current PHP information';
    }

    public function usage()
    {
        return 'phpbrew info';
    }

    public function execute()
    {
        $this->logger->warn(
            'The info command is deprecated and will be removed in the future.' . PHP_EOL
            . 'Please use `php -ini` instead.'
        );

        echo <<<'EOF'
<?php

echo "Version" . PHP_EOL;
echo 'PHP-', phpversion(), PHP_EOL, PHP_EOL;

echo "Constants", PHP_EOL;
$constants = get_defined_constants();

if (defined('PHP_PREFIX')) {
    echo 'PHP Prefix: ', PHP_PREFIX, PHP_EOL;
}

if (defined('PHP_BINARY')) {
    echo 'PHP Binary: ', PHP_BINARY, PHP_EOL;
}

if (defined('DEFAULT_INCLUDE_PATH')) {
    echo 'PHP Default Include path: ', DEFAULT_INCLUDE_PATH, PHP_EOL;
}

echo 'PHP Include path: ', get_include_path(), PHP_EOL, PHP_EOL;

echo "General Info", PHP_EOL;
phpinfo(INFO_GENERAL);
echo PHP_EOL;

echo "Extensions", PHP_EOL;
$extensions = get_loaded_extensions();
echo implode(', ', $extensions), PHP_EOL;
echo PHP_EOL;

echo "Database Extensions", PHP_EOL;
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
    echo $extName, PHP_EOL;
}
EOF;
    }
}

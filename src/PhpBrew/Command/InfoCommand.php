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

echo "Version\n";
echo 'PHP-', phpversion(), "\n\n";

echo "Constants\n";
$constants = get_defined_constants();

if (defined('PHP_PREFIX')) {
    echo 'PHP Prefix: ', PHP_PREFIX, "\n";
}

if (defined('PHP_BINARY')) {
    echo 'PHP Binary: ', PHP_BINARY, "\n";
}

if (defined('DEFAULT_INCLUDE_PATH')) {
    echo 'PHP Default Include path: ', DEFAULT_INCLUDE_PATH, "\n";
}

echo 'PHP Include path: ', get_include_path(), "\n\n";

echo "General Info\n";
phpinfo(INFO_GENERAL);
echo "\n";

echo "Extensions\n";
$extensions = get_loaded_extensions();
echo implode(', ', $extensions), "\n";
echo "\n";

echo "Database Extensions\n";
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
    echo $extName, "\n";
}
EOF;
    }
}

<?php
$console = new PhpBrew\Console;
try {
    if (isset($argv)) {
        $console->run( $argv );
    }
} catch ( Exception $e ) {
    echo $e->getMessage(), "\n";
    exit(-1);
}

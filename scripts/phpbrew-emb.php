<?php
$console = new PhpBrew\Console;
try {
    if ($argv) {
        $console->run( $argv );
    }
} catch ( Exception $e ) {
    echo $e->getMessage(), "\n";
    exit(-1);
}

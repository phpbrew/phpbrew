<?php
$console = new PhpBrew\Console;
if (isset($argv)) {
    if (!$console->runWithTry($argv)) {
        exit(-1);
    }
}

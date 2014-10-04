<?php
$console = new PhpBrew\Console;
global $argv;
if (!$console->runWithTry($argv)) {
    exit(-1);
}

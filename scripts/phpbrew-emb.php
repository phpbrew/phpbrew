<?php
$console = new PhpBrew\Console;
if (!$console->runWithTry($argv)) {
    exit(-1);
}

<?php
$console = PhpBrew\Console::getInstance();
if (isset($argv)) {
    if (!$console->runWithTry($argv)) {
        exit(-1);
    }
}

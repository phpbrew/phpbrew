<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Runs a PHP script that can be stopped only with a SIGKILL (9) signal for 3 seconds.
 *
 * @args duration Run this script with a custom duration
 *
 * @example `php NonStopableProcess.php 42` will run the script for 42 seconds
 */
function handleSignal($signal)
{
    switch ($signal) {
        case SIGTERM:
            $name = 'SIGTERM';
            break;
        case SIGINT:
            $name = 'SIGINT';
            break;
        default:
            $name = $signal.' (unknown)';
            break;
    }

    echo "signal $name\n";
}

pcntl_signal(SIGTERM, 'handleSignal');
pcntl_signal(SIGINT, 'handleSignal');

echo 'received ';

$duration = isset($argv[1]) ? (int) $argv[1] : 3;
$start = microtime(true);

while ($duration > (microtime(true) - $start)) {
    usleep(10000);
    pcntl_signal_dispatch();
}

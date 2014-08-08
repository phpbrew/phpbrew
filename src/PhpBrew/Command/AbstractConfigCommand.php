<?php
namespace PhpBrew\Command;

/**
 * A base for commands that need to open files into an external system app
 * using the same tty as phpbrew itself
 */
abstract class AbstractConfigCommand extends \CLIFramework\Command
{
    protected function editor($file)
    {
        $tty  = exec("tty");
        $editor = escapeshellarg(getenv('EDITOR') ?: 'nano');
        exec("{$editor} {$file} > {$tty}");
    }
}

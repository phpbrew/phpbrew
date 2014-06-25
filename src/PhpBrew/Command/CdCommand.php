<?php
namespace PhpBrew\Command;
use CLIFramework\Command;

class CdCommand extends VirtualCommand
{
    public function brief() { return 'Change to directories'; }

    public function usage() { return 'phpbrew cd [var|etc|build|dist]'; }
}

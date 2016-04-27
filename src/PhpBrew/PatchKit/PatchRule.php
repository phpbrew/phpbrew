<?php
namespace PhpBrew\PatchKit;
use PhpBrew\Buildable;
use CLIFramework\Logger;

interface PatchRule
{
    public function apply(Buildable $build, Logger $logger);

    public function backup(Buildable $build, Logger $logger);
}





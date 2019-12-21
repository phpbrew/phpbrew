<?php

namespace PHPBrew\PatchKit;

use CLIFramework\Logger;
use PHPBrew\Buildable;

interface PatchRule
{
    public function apply(Buildable $build, Logger $logger);

    public function backup(Buildable $build, Logger $logger);
}

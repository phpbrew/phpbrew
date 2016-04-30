<?php
namespace PhpBrew\Types;

use LogicException;
use PhpBrew\Types\Path;

class ExistingDirectory extends Path
{
    public function __construct($directory)
    {
        if (!is_dir($directory)) {
            throw new LogicException("$directory is not a directory.");
        }
        parent::__construct($directory);
    }
}

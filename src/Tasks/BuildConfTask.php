<?php

namespace PHPBrew\Tasks;

use PHPBrew\Build;
use PHPBrew\Exception\SystemCommandException;

/**
 * Task to run `./buildconf`.
 */
class BuildConfTask extends BaseTask
{
    public function run(Build $build)
    {
        $lastLine = system('./buildconf --force', $status);

        if ($status !== 0) {
            throw new SystemCommandException(
                sprintf('buildconf error: %s', $lastLine),
                $build
            );
        }
    }
}

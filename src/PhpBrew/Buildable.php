<?php

namespace PhpBrew;

interface Buildable
{
    public function getSourceDirectory();

    public function isBuildable();

    /**
     * @return string return build log file path.
     */
    public function getBuildLogPath();
}

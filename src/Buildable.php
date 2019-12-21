<?php

namespace PHPBrew;

interface Buildable
{
    /**
     * @return path return source directory
     */
    public function getSourceDirectory();

    /**
     * @return boolean
     */
    public function isBuildable();

    /**
     * @return string return build log file path.
     */
    public function getBuildLogPath();
}

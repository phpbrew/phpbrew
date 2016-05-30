<?php

namespace PhpBrew;

interface Buildable
{
    public function getSourceDirectory();

    public function isBuildable();
}

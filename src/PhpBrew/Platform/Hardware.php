<?php

namespace PhpBrew\Platform;

interface Hardware
{
    public function is32bit();
    public function is64bit();
}

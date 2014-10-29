<?php

namespace PhpBrew;

use CLIFramework\Logger;

interface ExtensionInterface
{
    public function isLoaded();
    public function isInstalled();
    public function isAvailable();
}

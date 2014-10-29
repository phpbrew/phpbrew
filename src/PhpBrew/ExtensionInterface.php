<?php

namespace PhpBrew;

use CLIFramework\Logger;

interface ExtensionInterface
{
    public function __construct($name);
    public function isLoaded();
    public function isInstalled();
    public function isAvailable();
}

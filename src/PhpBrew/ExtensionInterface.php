<?php

namespace PhpBrew;

use CLIFramework\Logger;

interface ExtensionInterface
{
    public function __construct($name, Logger $logger);
    public function install($version, array $options = array());
    public function enable();
    public function disable();
    public function isLoaded();
    public function isAvailable();
}

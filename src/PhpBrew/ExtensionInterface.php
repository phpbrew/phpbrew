<?php

namespace PhpBrew;

interface ExtensionInterface
{
    public function __construct($name, $logger);
    public function install($version, array $options = array());
    public function enable();
    public function isLoaded();
    public function isAvailable();
}

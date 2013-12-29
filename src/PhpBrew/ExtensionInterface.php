<?php

namespace PhpBrew;

interface ExtensionInterface
{
	public function __construct($name, $logger);
	public function install($version, array $options);
    public function enable();
    public function disable();
	public function isLoaded();
	public function isAvailable();
}
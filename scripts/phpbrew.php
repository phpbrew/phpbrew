#!/usr/bin/env php
<?php
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader( array('src', 'vendor/pear' ));
$loader->useIncludePath(true);
$loader->register();

$console = new PhpBrew\Console;
$console->run( $argv );

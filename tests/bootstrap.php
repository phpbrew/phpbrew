<?php
require 'tests/helpers.php';
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader( array(
    dirname(dirname(__FILE__)) . '/src', 
    dirname(dirname(__FILE__)) . '/vendor/pear' 
));
$loader->register();

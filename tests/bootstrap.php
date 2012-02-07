<?php
require 'tests/helpers.php';
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader( 
    array('src', 'vendor/pear' 
));
$loader->register();

<?php
require 'tests/helpers.php';
require 'src/Universal/ClassLoader/SplClassLoader.php';
$loader = new \Universal\ClassLoader\SplClassLoader( array(  
    'Universal' => 'src'
));
$loader->register();

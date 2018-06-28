<?php
require 'tests/bootstrap.php';

$ser = new SerializerKit\YamlSerializer;
echo $ser->encode(array( 
    'foo' => 'a',
    'bar' => 'b',
    'list' => array( 1,2,3,4 ),
));



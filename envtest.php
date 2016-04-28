<?php
require 'tests/bootstrap.php';

var_dump(putenv('PHPBREW_PHP=5.5-dev'));
var_dump(getenv('PHPBREW_PHP'));

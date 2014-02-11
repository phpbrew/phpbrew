#!/usr/bin/env bash

source scripts/upgrade

## can only upgrade if current version is from phpbrew - in other words if phpbrew is active
oldPhp=$PHPBREW_PHP
unset PHPBREW_PHP
is_phpbrew_active # status=1
PHPBREW_PHP=$oldPhp
is_phpbrew_active # status=0

## can get current php version
PHPBREW_PHP=php-5.5.7
get_current_php_major_version # match=/^php-5.5$/

## can get latest minor release of php major version
get_major_release_for php-5.3 # match=/^5.3.28$/

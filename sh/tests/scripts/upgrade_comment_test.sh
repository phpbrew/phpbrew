#!/usr/bin/env bash

source scripts/upgrade

## can only upgrade if current version is from phpbrew - in other words if phpbrew is active
oldPhp=$PHPBREW_PHP
unset PHPBREW_PHP
is_phpbrew_active # status=1
PHPBREW_PHP=$oldPhp
is_phpbrew_active # status=0

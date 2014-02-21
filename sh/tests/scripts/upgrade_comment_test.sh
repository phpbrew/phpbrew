#!/usr/bin/env bash

source tests/mock/upgrade_mock.sh
source scripts/upgrade

## can only upgrade if current version is from phpbrew - in other words if phpbrew is active
oldPhp=$PHPBREW_PHP
unset PHPBREW_PHP
__is_phpbrew_active # status=1
PHPBREW_PHP=$oldPhp
__is_phpbrew_active # status=0

## can get current php version
PHPBREW_PHP=php-5.3.27
__get_current_php_major_version # match=/^php-5.3$/

## can get latest minor release of php major version
__get_major_release_for php-5.3 # match=/^5.3.28$/

## can get current php variants
__get_current_variants # match=/^\+pdo \+mysql \+pgsql=/opt/local/lib/postgresql92/bin \+fpm \+sqlite \+pcntl \+posix \+gettext \+intl \+openssl \+default -pgsql -- --with-icu-dir=/opt/local --with-mcrypt=/opt/local --enable-maintainer-zts --enable-debug$/

## can list ext-installed extensions
__get_installed_exts 5.3.27 # match=/^curl openssl xdebug$/

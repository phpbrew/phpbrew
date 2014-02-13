#!/usr/bin/env bash


cat() {
  if [[ "$1" = "$PHPBREW_HOME/php/$PHPBREW_PHP/phpbrew.variants" ]]
  then
    echo 'a:3:{s:16:"enabled_variants";a:11:{s:3:"pdo";b:1;s:5:"mysql";b:1;s:5:"pgsql";s:31:"/opt/local/lib/postgresql92/bin";s:3:"fpm";b:1;s:6:"sqlite";b:1;s:5:"pcntl";b:1;s:5:"posix";b:1;s:7:"gettext";b:1;s:4:"intl";b:1;s:7:"openssl";b:1;s:7:"default";b:1;}s:17:"disabled_variants";a:1:{s:5:"pgsql";b:1;}s:13:"extra_options";a:4:{i:0;s:25:"--with-icu-dir=/opt/local";i:1;s:24:"--with-mcrypt=/opt/local";i:2;s:23:"--enable-maintainer-zts";i:3;s:14:"--enable-debug";}}'
  else
    command -p cat $1
  fi
}

phpbrew() {
  if [[ "$1" = "known" ]]
  then
    echo "Available stable versions:
    5.5 versions:    5.5.9, 5.5.8, 5.5.7, 5.5.6, 5.5.5, 5.5.4, 5.5.3, 5.5.2
    5.4 versions:    5.4.25, 5.4.24, 5.4.23, 5.4.22, 5.4.21, 5.4.20, 5.4.19, 5.4.18
    5.3 versions:    5.3.28, 5.3.27, 5.3.26, 5.3.25, 5.3.24, 5.3.23, 5.3.22, 5.3.21"
  fi
  return 0
}

#!/usr/bin/env bash

source tests/mock/installer_mock.sh

source bin/installer
set +o errexit

## set prefix to $HOME/.phpbrew if installed as normal user
phpbrew_install_set_defaults
[[ "$HOME" = "$phpbrew_prefix" ]] # status=0

## set prefix to /usr/local if installed as root
## trick to "mock" UID: http://lists.gnu.org/archive/html/bug-bash/2000-09/msg00019.html
env - UID=0 /usr/bin/env sh -c "source bin/installer; phpbrew_install_set_defaults; echo \$phpbrew_prefix" # match=/\/usr\/local/

## can check os
mock_lsb_release "Ubuntu"
phpbrew_check_system # env[phpbrew_os]=~/^Ubuntu$/

## error on unsupported os
mock_lsb_release "unknown"
phpbrew_check_system # status=1

## osx requirements can be installed with macports
export phpbrew_os="Darwin"
phpbrew_install_dependencies # env[port_args]=~/^install curl automake autoconf icu depof:php5 depof:php5-gd mcrypt re2c gettext openssl$/

## osx requirements can be installed with homebrew
export phpbrew_os="Darwin"
unset port
phpbrew_install_dependencies # env[brew_args]=~/^ install automake autoconf curl pcre re2c mhash libtool icu4c gettext jpeg libxml2 mcrypt gmp libevent link icu4c$/

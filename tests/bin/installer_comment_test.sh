#!/usr/bin/env bash

source bin/installer

set +o errtrace
set +o errexit

## set prefix to $HOME/.phpbrew if installed as normal user
sh -c "source bin/installer; phpbrew_install_set_defaults; if [[ \"\$HOME\" = \"\$phpbrew_prefix\" ]]; then exit 0; else exit 1; fi" # status=0

## set prefix to /usr/local if installed as root
## trick to "mock" UID: http://lists.gnu.org/archive/html/bug-bash/2000-09/msg00019.html
env - UID=0 /usr/bin/env sh -c "source bin/installer; phpbrew_install_set_defaults; echo \$phpbrew_prefix" # match=/\/usr\/local/

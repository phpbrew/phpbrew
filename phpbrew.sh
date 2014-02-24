#!/bin/bash
# Brought from gugod's perlbrew.
# Author: Yo-An Lin
# NOTICE: This script is for local testing, to release updated script, 
# please also modify the src/PhpBrew/Command/InitCommand.php

# default phpbrew root and phpbrew home path


# PHPBREW_HOME: contains the phpbrew config (for users)
# PHPBREW_ROOT: contains installed php(s) and php source files.
# PHPBREW_SKIP_INIT: if you need to skip loading config from the init file. 
# PHPBREW_PHP:  the current php version.
# PHPBREW_PATH: the bin path of the current php.

[[ -z "$PHPBREW_HOME" ]] && export PHPBREW_HOME="$HOME/.phpbrew"

if [[ -z "$PHPBREW_SKIP_INIT" ]]; then
    # load user-defined config
    if [[ -f $PHPBREW_HOME/init ]]; then
        . $PHPBREW_HOME/init
        export PATH=$PHPBREW_PATH:$PATH
    fi
fi

[[ -z "$PHPBREW_ROOT" ]] && export PHPBREW_ROOT="$HOME/.phpbrew"
[[ -z "$PHPBREW_BIN" ]] && export PHPBREW_BIN="$PHPBREW_ROOT/.phpbrew/bin"

[[ -e "$PHPBREW_ROOT" ]] || mkdir $PHPBREW_ROOT
[[ -e "$PHPBREW_HOME" ]] || mkdir $PHPBREW_HOME

[[ ! -e $PHPBREW_BIN ]] && mkdir -p $PHPBREW_BIN



function __wget_as ()
{
    local url=$1
    local target=$2
    wget --no-check-certificate -c $url -O $target
}


function __phpbrew_set_lookup_prefix ()
{
    case $1 in
        debian|ubuntu|linux)
            # echo /usr/lib/x86_64-linux-gnu:/usr/lib/i386-linux-gnu
            echo /usr
        ;;
        macosx)
            echo /usr
        ;;
        macports)
            echo /opt/local
        ;;
        homebrew)
            echo /usr/local/Cellar:/usr/local
        ;;
        *)
            if [[ -e $1 ]] ; then
                echo $1
            else
                echo /usr
            fi
        ;;
    esac
}


function phpbrew ()
{
    # Check bin/phpbrew if we are in PHPBrew source directory, 
    # This is only for development
    if [[ -e bin/phpbrew ]] ; then
        BIN='bin/phpbrew'
    else
        BIN='phpbrew'
    fi

    local exit_status
    local short_option
    export SHELL
    if [[ `echo $1 | awk 'BEGIN{FS=""}{print $1}'` = '-' ]]
    then
        short_option=$1
        shift
    else
        short_option=""
    fi


    case $1 in
        use) if [[ -z "$2" ]]
            then
                if [[ -z "$PHPBREW_PHP" ]]
                then
                    echo "Currently using system php"
                else
                    echo "Currently using $PHPBREW_PHP"
                fi
            else
                if [[ $2 =~ ^php- ]]
                then
                    _PHP_VERSION=$2
                else
                    _PHP_VERSION="php-$2"
                fi

                if [[ $_PHP_VERSION =~ ^php-[0-9]*\.[0-9]*$ ]]
                then
                  _PHP_VERSION=$(find $PHPBREW_HOME/php -name "$_PHP_VERSION*" -maxdepth 1 | sort -nr | head -1 | sed "s/${PHPBREW_HOME//\//\\/}\/php\///")
                fi

                # checking php version exists?
                NEW_PHPBREW_PHP_PATH="$PHPBREW_ROOT/php/$_PHP_VERSION"
                if [ -d $NEW_PHPBREW_PHP_PATH ]; then
                    code=$(command $BIN env $_PHP_VERSION)
                    if [ -z "$code" ]
                    then
                        exit_status=1
                    else
                        eval $code
                        __phpbrew_set_path
                    fi
                else
                    echo "php version: $_PHP_VERSION not exists."
                fi
            fi
            ;;
        cd-src)
            local SOURCE_DIR=$PHPBREW_HOME/build/$PHPBREW_PHP
            if [[ -d $SOURCE_DIR ]] ; then
                cd $SOURCE_DIR
            fi
            ;;
        switch)
            if [[ -z "$2" ]]
            then
                echo "Please specify the php version."
            else
                __phpbrew_reinit $2
            fi
            ;;
        lookup-prefix)
            if [[ -z "$2" ]] ; then
                if [[ -n $PHPBREW_LOOKUP_PREFIX ]] ; then
                    echo $PHPBREW_LOOKUP_PREFIX
                fi
            else
                export PHPBREW_LOOKUP_PREFIX=$(__phpbrew_set_lookup_prefix $2)
                echo $PHPBREW_LOOKUP_PREFIX
                __phpbrew_update_config
            fi
            ;;
        install-pyrus)
            echo "Installing pyrus..."
            cd $PHPBREW_BIN && \
                wget --no-check-certificate -c http://pear2.php.net/pyrus.phar -O pyrus && \
                chmod +x pyrus && \
                cd -
            hash -r
            ;;
        install-phpunit)
            pear channel-discover pear.phpunit.de
            pear install -a phpunit/PHPUnit
            hash -r
            ;;
        install-composer)
            echo "Installing composer..."
            cd $PHPBREW_BIN && \
                wget --no-check-certificate -c http://getcomposer.org/composer.phar -O composer && \
                chmod +x composer && \
                cd -
            hash -r
            ;;
        install-onion)
            echo "Installing onion..."
            cd $PHPBREW_BIN
            wget --no-check-certificate -c https://raw.github.com/c9s/Onion/master/onion -O onion
            chmod +x onion
            cd -
            hash -r
            ;;
        cd)
            case $2 in
                var)
                    local chdir=$PHPBREW_ROOT/php/$PHPBREW_PHP/var
                    ;;
                etc)
                    local chdir=$PHPBREW_ROOT/php/$PHPBREW_PHP/etc
                    ;;
                dist)
                    local chdir=$PHPBREW_ROOT/php/$PHPBREW_PHP
                    ;;
                build)
                    local chdir=$PHPBREW_ROOT/build/$PHPBREW_PHP
                    ;;
                *)
                    echo "$2 not found"
                    return 0
                ;;
            esac
            echo "Switching to $chdir, run 'cd -' to go back."
            cd $chdir
            return 0
            ;;
        config)
            if [[ -n $EDITOR ]] ; then
                $EDITOR $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini
            else
                echo "Please set EDITOR environment variable for your favor."
                nano $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini
            fi
            ;;
        clean)
            local _VERSION=$2
            if [[ -z $_version ]] ; then
                _VERSION=$PHPBREW_PHP
            fi
            echo "Cleaning up $_VERSION build directory..."
            local build_dir=$PHPBREW_ROOT/build/$_VERSION
            echo "build_dir=$build_dir"
            if [[ -e $build_dir ]] ; then
                cd $build_dir && make clean && cd -
            fi
            ;;
        ext)
            case $2 in
                disable)
                    echo "Disabling extension..."
                    if [[ -e "$PHPBREW_ROOT/php/$PHPBREW_PHP/var/db/$3.ini.disabled" ]]; then
                      echo "[ ] $3 extension is already disabled"
                    else
                      if [[ -e "$PHPBREW_ROOT/php/$PHPBREW_PHP/var/db/$3.ini" ]]; then
                        mv $PHPBREW_ROOT/php/$PHPBREW_PHP/var/db/$3.ini $PHPBREW_ROOT/php/$PHPBREW_PHP/var/db/$3.ini.disabled
                        echo "[ ] $3 extension is disabled"
                      else
                        echo "Failed to disable $3 extension. Maybe it's not installed yet?"
                        return 1
                      fi
                    fi
                ;;
                *)
                    command $BIN ${*:1}
                ;;
            esac
            ;;
        fpm)
            PHPFPM_BIN=$PHPBREW_ROOT/php/$PHPBREW_PHP/sbin/php-fpm
            PHPFPM_PIDFILE=$PHPBREW_ROOT/php/$PHPBREW_PHP/var/run/php-fpm.pid
            mkdir -p $PHPBREW_ROOT/php/$PHPBREW_PHP/var/run
            function fpm_start()
            {
              echo "Starting php-fpm..."
              $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini \
                --fpm-config $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php-fpm.conf \
                --pid $PHPFPM_PIDFILE \
                ${*:3}
              if [[ $? != "0" ]] ; then
                echo "php-fpm start failed."
              fi
            }
            function fpm_stop()
            {
              if [[ -e $PHPFPM_PIDFILE ]] ; then
                echo "Stopping php-fpm..."
                kill $(cat $PHPFPM_PIDFILE)
                rm -f $PHPFPM_PIDFILE
              fi
            }
            case $2 in
                start)
                    fpm_start
                    ;;
                stop)
                    fpm_stop
                    ;;
                restart)
                    fpm_stop
                    fpm_start
                    ;;
                module)
                    $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php-fpm.conf \
                            -m | less
                    ;;
                info)
                    $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php-fpm.conf \
                            -i
                    ;;
                config)
                    if [[ -n $EDITOR ]] ; then
                        $EDITOR $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php-fpm.conf
                    else
                        echo "Please set EDITOR environment variable for your favor."
                        nano $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php-fpm.conf
                    fi
                    ;;
                help)
                    $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php-fpm.conf --help
                    ;;
                test)
                    $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php-fpm.conf --test
                    ;;
                *)
                    echo "Usage: phpbrew fpm [start|stop|restart|module|test|help|config]"
                    ;;
            esac
            ;;
        env)
            # we don't check php path here, you should check path before you
            # use env command to output the environment config.
            if [[ -n "$2" ]]; then
                export PHPBREW_PHP=$2
            fi
            echo "export PHPBREW_ROOT=$PHPBREW_ROOT";
            echo "export PHPBREW_HOME=$PHPBREW_HOME";
            if [[ -n $PHPBREW_LOOKUP_PREFIX ]] ; then
                echo "export PHPBREW_LOOKUP_PREFIX=$PHPBREW_LOOKUP_PREFIX";
            fi
            if [[ -n $PHPBREW_PHP ]] ; then
                echo "export PHPBREW_PHP=$PHPBREW_PHP";
                echo "export PHPBREW_PATH=$PHPBREW_ROOT/php/$PHPBREW_PHP/bin";
            fi
            ;;
        off)
            unset PHPBREW_PHP
            unset PHPBREW_PATH
            eval `$BIN env`
            __phpbrew_set_path
            echo "phpbrew is turned off."
            ;;
        switch-off)
            unset PHPBREW_PHP
            unset PHPBREW_PATH
            eval `$BIN env`
            __phpbrew_set_path
            __phpbrew_reinit
            echo "phpbrew is switched off."
            ;;
        remove)
            if [[ -z "$2" ]]
            then
                command $BIN help
            else
              __phpbrew_remove_purge $2
            fi
            ;;
        rehash)
            echo "Rehashing..."
            . ~/.phpbrew/bashrc
            ;;
        purge)
            if [[ -z "$2" ]]
            then
                command $BIN help
            else
              __phpbrew_remove_purge $2 purge
            fi
            ;;
        upgrade)
            shift
            __phpbrew_upgrade $*
            ;;
        *)
            command $BIN $short_option "$@"
            exit_status=$?
            ;;
    esac
    hash -r
    return ${exit_status:-0}
}

function __phpbrew_set_path ()
{
    [[ -n $(alias php 2>/dev/null) ]] && unalias php 2> /dev/null

    if [[ -n $PHPBREW_ROOT ]] ; then
        export PATH_WITHOUT_PHPBREW=$(perl -e 'print join ":", grep { index($_,$ENV{PHPBREW_ROOT}) } split/:/,$ENV{PATH};')
    fi

    if [[ -z "$PHPBREW_PATH" ]]
    then
        export PATH=$PHPBREW_BIN:$PATH_WITHOUT_PHPBREW
    else
        export PATH=$PHPBREW_PATH:$PHPBREW_BIN:$PATH_WITHOUT_PHPBREW
    fi
    # echo "PATH => $PATH"
}

function __phpbrew_update_config ()
{
    local VERSION=$1
    echo '# DO NOT EDIT THIS FILE' >| "$PHPBREW_HOME/init"
    command $BIN env $VERSION >> "$PHPBREW_HOME/init"
    . "$PHPBREW_HOME/init"
}

function __phpbrew_reinit () 
{
    if [[ $1 =~ ^php- ]]
    then
        local _PHP_VERSION=$1
    else
        local _PHP_VERSION="php-$1"
    fi

    if [[ $_PHP_VERSION =~ ^php-[0-9]*\.[0-9]*$ ]]
    then
      _PHP_VERSION=$(find $PHPBREW_HOME/php -name "$_PHP_VERSION*" -maxdepth 1 | sort -nr | head -1 | sed "s/${PHPBREW_HOME//\//\\/}\/php\///")
    fi

    if [[ ! -d "$PHPBREW_HOME" ]]
    then
        mkdir -p -p "$PHPBREW_HOME"
    fi
    __phpbrew_update_config $_PHP_VERSION
    __phpbrew_set_path
}

function __phpbrew_remove_purge ()
{
    if [[ $1 =~ ^php- ]]
    then
        _PHP_VERSION=$1
    else
        _PHP_VERSION="php-$1"
    fi

    if [[ "$_PHP_VERSION" = "$PHPBREW_PHP" ]]
    then
        echo "php version: $_PHP_VERSION is already in used."
        return 1
    fi

    _PHP_BIN_PATH=$PHPBREW_ROOT/php/$_PHP_VERSION
    _PHP_SOURCE_FILE=$PHPBREW_ROOT/build/$_PHP_VERSION.tar.bz2
    _PHP_BUILD_PATH=$PHPBREW_ROOT/build/$_PHP_VERSION

    if [ -d $_PHP_BIN_PATH ]; then

        if [[ "$2" = "purge" ]]
        then
            rm -f $_PHP_SOURCE_FILE
            rm -fr $_PHP_BUILD_PATH
            rm -fr $_PHP_BIN_PATH

            echo "php version: $_PHP_VERSION is removed and purged."
        else
            rm -f $_PHP_SOURCE_FILE
            rm -fr $_PHP_BUILD_PATH

            for FILE1 in $_PHP_BIN_PATH/*
            do
                if [[ "$FILE1" != "$_PHP_BIN_PATH/etc" ]] && [[ "$FILE1" != "$_PHP_BIN_PATH/var" ]]
                then
                    rm -fr $FILE1;
                fi
            done

            echo "php version: $_PHP_VERSION is removed."
        fi

    else
        echo "php version: $_PHP_VERSION not installed."
    fi

    return 0
}

__is_phpbrew_active() {
  if [[ -n "$PHPBREW_PHP" ]]; then
return 0
  else
return 1
  fi
}

__get_current_php_major_version() {
  echo ${PHPBREW_PHP%.[0-9]*}
}

__is_version_lower_than() {
  [ "$1" = "$2" ] && return 1 || [ "$1" = "$(echo -e "$1\n$2" | sort | head -n1)" ]
}

__get_major_release_for() {
  major_version="$1"
  latest_minor_version=''

  oldIFS="$IFS"
  all_minor_version=( $(phpbrew known) )
  IFS="$oldIFS"

  for version in ${all_minor_version[@]}; do
version=${version%,}
    if [[ "$version" =~ ^${major_version#php-}.[0-9]+$ ]]; then
if [[ -n "$latest_minor_version" ]]; then
if [ $(__is_version_lower_than "$latest_minor_version" "$version") ]; then
latest_minor_version=$version
        fi
else
latest_minor_version=$version
      fi
fi
done

echo $latest_minor_version
}

__get_installed_exts() {
  ls $PHPBREW_HOME/php/php-$1/var/db | sed -ne 's/\([^.]*\)\.ini\(\.disabled\)*/\1/pg'
}

__phpbrew_upgrade() {
    keep_current=false

    while getopts "k" option
    do
    case $option in
            k) keep_current=true;;
        esac
    done

    current_php_version=${PHPBREW_PHP#php-}
    new_php_version=$(__get_major_release_for $(__get_current_php_major_version))

    if test "$current_php_version" = "$new_php_version"
    then
        echo "You are already up-to-date!"
        return 0
    fi

    phpbrew install --like $current_php_version $new_php_version

    if test -d $PHPBREW_HOME/php/php-$1/var/db
    then
        phpbrew use $new_php_version
        __get_installed_exts $current_php_version | awk '{ system("phpbrew ext install "$1) }'
        phpbrew use $current_php_version
    fi

    cp -RPp $PHPBREW_HOME/php/php-$current_php_version/etc/* $PHPBREW_HOME/php/php-$new_php_version/etc

    phpbrew switch $new_php_version

    if test $keep_current = false
    then
        phpbrew purge $current_php_version
    fi
}

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

[[ ! -e $PHPBREW_BIN ]] && mkdir -p $PHPBREW_BIN


function __phpbrew_set_lookup_prefix ()
{
    case $1 in
        macosx|debian|debian|ubuntu|redhat|fedora)
            echo "/usr"
        ;;
        macports)
            echo "/opt/local"
        ;;
        homebrew)
            echo "/usr/local"
        ;;
        *)
            if [[ -e $1 ]] ; then
                echo $1
            else
                echo "/usr"
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
        install-phpunit)
            pear channel-discover pear.phpunit.de
            pear install -a phpunit/PHPUnit
            hash -r
            ;;
        install-composer)
            echo "Installing composer..."
            cd $PHPBREW_BIN
            wget --no-check-certificate -c --no-verbose http://getcomposer.org/composer.phar -O composer
            chmod +x $PHPBREW_BIN/composer
            cd -
            hash -r
            ;;
        install-onion)
            echo "Installing onion..."
            cd $PHPBREW_BIN
            wget --no-check-certificate -c --no-verbose https://raw.github.com/c9s/Onion/master/onion -O onion
            chmod +x onion
            cd -
            hash -r
            ;;
        var-dir)
            local chdir=$PHPBREW_ROOT/php/$PHPBREW_PHP/var
            echo "Switching to $chdir"
            cd $chdir
            ;;
        etc-dir)
            local chdir=$PHPBREW_ROOT/php/$PHPBREW_PHP/etc
            echo "Switching to $chdir"
            cd $chdir
            ;;
        dist-dir)
            local chdir=$PHPBREW_ROOT/php/$PHPBREW_PHP
            echo "Switching to $chdir"
            cd $chdir
            ;;
        build-dir)
            local chdir=$PHPBREW_ROOT/build/$PHPBREW_PHP
            echo "Switching to $chdir"
            cd $chdir
            ;;
        config)
            if [[ -n $EDITOR ]] ; then
                $EDITOR $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini
            else
                echo "Please set EDITOR environment variable for your favor."
                nano $PHPBREW_ROOT/php/$PHPBREW_PHP/etc/php.ini
            fi
            ;;
        ext)
            case $2 in
                disable)
                    echo "Removing extension config..."
                    rm -fv $PHPBREW_ROOT/php/$PHPBREW_PHP/var/db/$3.ini
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

    _PHP_BIN_PATH=$PHPBREW_HOME/php/$_PHP_VERSION
    _PHP_SOURCE_FILE=$PHPBREW_HOME/build/$_PHP_VERSION.tar.bz2
    _PHP_BUILD_PATH=$PHPBREW_HOME/build/$_PHP_VERSION

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

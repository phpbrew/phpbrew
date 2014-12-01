#!/usr/bin/env fish
# Authors:
#   - Yo-An Lin
#   - Márcio Almada
#   - Rack Lin

# PHPBrew defaults:
# PHPBREW_HOME: contains the phpbrew config (for users)
# PHPBREW_ROOT: contains installed php(s) and php source files.
# PHPBREW_SKIP_INIT: if you need to skip loading config from the init file. 
# PHPBREW_PHP:  the current php version.
# PHPBREW_PATH: the bin path of the current php.


# export alias for bourne shell compatibility.
# From PR https://github.com/fish-shell/fish-shell/pull/1833
if not functions --query export
  function export --description 'Set global variable. Alias for set -g, made for bash compatibility'
          if test -z "$argv"
             set
             return 0
          end
          for arg in $argv
              set -l v (echo $arg|tr '=' \n)
              set -l c (count $v)
              switch $c
                      case 1
                              set -gx $v $$v
                      case 2
                              set -gx $v[1] $v[2]
              end
          end
  end
end


[ -z "$PHPBREW_HOME" ]; and set -gx PHPBREW_HOME "$HOME/.phpbrew"

function __phpbrew_load_user_config
    # load user-defined config
    if [ -f $PHPBREW_HOME/init ]
        . $PHPBREW_HOME/init
        set -gx PATH $PHPBREW_PATH $PATH
    end
end

if [ -z "$PHPBREW_SKIP_INIT" ]
    __phpbrew_load_user_config
end

[ -z "$PHPBREW_ROOT" ]; and set -gx PHPBREW_ROOT "$HOME/.phpbrew"
[ -z "$PHPBREW_BIN" ]; and set -gx PHPBREW_BIN "$PHPBREW_HOME/bin"
[ -z "$PHPBREW_VERSION_REGEX" ]; and set -gx PHPBREW_VERSION_REGEX '^([[:digit:]]+\.){2}[[:digit:]]+$'

[ ! -d "$PHPBREW_ROOT" ]; and mkdir $PHPBREW_ROOT
[ ! -d "$PHPBREW_HOME" ]; and mkdir $PHPBREW_HOME

[ ! -d $PHPBREW_BIN ]; and mkdir -p $PHPBREW_BIN

function __wget_as
    set -l url $argv[1]
    set -l target $argv[2]
    wget --no-check-certificate -c $url -O $target
end

function __phpbrew_set_lookup_prefix
    switch (echo $argv[1])
        case debian ubuntu linux
            # echo /usr/lib/x86_64-linux-gnu:/usr/lib/i386-linux-gnu
            echo /usr
        case macosx
            echo /usr
        case macports
            echo /opt/local
        case homebrew
            echo /usr/local/Cellar:/usr/local
        case '*'
            if [ -e $argv[1] ]
                echo $argv[1]
            else
                for dir in /opt/local /usr/local/Cellar /usr/local /usr
                    if [ -e $dir ]
                        echo $dir
                        return
                    end
                end
            end
    end
end

function phpbrew
    # Check bin/phpbrew if we are in PHPBrew source directory, 
    # This is only for development
    if [ -e bin/phpbrew ]
        set -g BIN 'bin/phpbrew'
    else
        set -g BIN 'phpbrew'
    end

    set exit_status
    set short_option
    # export SHELL
    if [ (echo $argv[1] | awk 'BEGIN{FS=""}{print $1}') = '-' ]
        set short_option $argv[1]
        set -e argv[1]
    else
        set short_option ""
    end


    switch (echo $argv[1])
        case use
            if [ (count $argv) -eq 1 ]
                if [ -z "$PHPBREW_PHP" ]
                    echo "Currently using system php"
                else
                    echo "Currently using $PHPBREW_PHP"
                end
            else
                if begin ; [ ! -d "$PHPBREW_ROOT/php/$argv[2]" ]; and echo $argv[2] | egrep -q -e $PHPBREW_VERSION_REGEX; end
                    set _PHP_VERSION "php-$argv[2]"
                else
                    set _PHP_VERSION $argv[2]
                end

                # checking php version exists?
                set NEW_PHPBREW_PHP_PATH "$PHPBREW_ROOT/php/$_PHP_VERSION"
                if [ -d $NEW_PHPBREW_PHP_PATH ]
                    if [ $BIN = "phpbrew" ]
                        set code (command phpbrew env $_PHP_VERSION)
                    else
                        set code (eval $BIN env $_PHP_VERSION)
                    end
                    if [ -z "$code" ]
                        set exit_status 1
                    else
                        eval $code
                        __phpbrew_set_path
                    end
                else
                    echo "php version: $_PHP_VERSION not exists."
                end
            end
        case cd-src
            set -l SOURCE_DIR $PHPBREW_HOME/build/$PHPBREW_PHP
            if [ -d $SOURCE_DIR ]
                cd $SOURCE_DIR
            end
        case 'switch'
            if [ (count $argv) -eq 1 ]
                echo "Please specify the php version."
            else
                __phpbrew_reinit $argv[2]
            end
        case lookup-prefix
            if [ (count $argv) -eq 1 ]
                if [ -n "$PHPBREW_LOOKUP_PREFIX" ]
                    echo $PHPBREW_LOOKUP_PREFIX
                end
            else
                set -gx PHPBREW_LOOKUP_PREFIX (__phpbrew_set_lookup_prefix $argv[2])
                echo $PHPBREW_LOOKUP_PREFIX
                __phpbrew_update_config
            end
        case install-pyrus
            echo "Installing pyrus..."
            cd $PHPBREW_BIN ; and \
                wget --no-check-certificate -c http://pear2.php.net/pyrus.phar -O pyrus ; and \
                chmod +x pyrus ; and \
                cd -
            hash -r
        case install-phpunit
            echo "Installing phpunit..."
            cd $PHPBREW_BIN ; and \
                wget --no-check-certificate -c https://phar.phpunit.de/phpunit.phar -O phpunit ; and \
                chmod +x phpunit ; and \
                cd -
            hash -r
        case install-composer
            echo "Installing composer..."
            cd $PHPBREW_BIN ; and \
                wget --no-check-certificate -c http://getcomposer.org/composer.phar -O composer ; and \
                chmod +x composer ; and \
                cd -
            hash -r
        case install-onion
            echo "Installing onion..."
            cd $PHPBREW_BIN ; and \
              wget --no-check-certificate -c https://raw.github.com/c9s/Onion/master/onion -O onion ; and \
              chmod +x onion ; and \
              cd -
            hash -r
        case cd
            if [ (count $argv) -eq 1 ]; return 0; end

            switch $argv[2]
                case var
                    set chdir $PHPBREW_ROOT/php/$PHPBREW_PHP/var
                case etc
                    set chdir $PHPBREW_ROOT/php/$PHPBREW_PHP/etc
                cas dist
                    set chdir $PHPBREW_ROOT/php/$PHPBREW_PHP
                case build
                    set chdir $PHPBREW_ROOT/build/$PHPBREW_PHP
                case '*'
                    echo "$argv[2] not found"
                    return 0
            end
            echo "Switching to $chdir, run 'cd -' to go back."
            cd $chdir
            return 0

        case fpm
            if [ (count $argv) -ge 3 ]
              set _PHP_VERSION $argv[3]
            else
              set _PHP_VERSION $PHPBREW_PHP
            end

            mkdir -p $PHPBREW_ROOT/php/$_PHP_VERSION/var/run
            set PHPFPM_BIN $PHPBREW_ROOT/php/$_PHP_VERSION/sbin/php-fpm
            set PHPFPM_PIDFILE $PHPBREW_ROOT/php/$_PHP_VERSION/var/run/php-fpm.pid

            function fpm_start
              echo "Starting php-fpm..."
              set -l regex '^php-5\.2.*'

              if echo $_PHP_VERSION | egrep -q -e $regex
                eval $PHPFPM_BIN start
              else
                eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini \
                  --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf \
                  --pid $PHPFPM_PIDFILE \
                  $argv[4..-1]
              end

              if [ "$status" != "0" ]
                echo "php-fpm start failed."
              end
            end

            function fpm_stop
              set -l regex '^php-5\.2.*'

              if echo $_PHP_VERSION | egrep -q -e $regex
                eval $PHPFPM_BIN stop
              else if [ -e $PHPFPM_PIDFILE ]
                echo "Stopping php-fpm..."
                kill (cat $PHPFPM_PIDFILE)
                rm -f $PHPFPM_PIDFILE
              end
            end

            [ (count $argv) -lt 2 ]; and $argv[2] = ''

            switch $argv[2]
              case start
                    fpm_start
              case stop
                    fpm_stop
              case restart
                    fpm_stop
                    fpm_start
              case module
                    eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf \
                            -m | less
              case info
                    eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf \
                            -i
              case config
                    if [ -n "$EDITOR" ]
                        eval $EDITOR $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf
                    else
                        echo "Please set EDITOR environment variable for your favor."
                        nano $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf
                    end
              case help
                    eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf --help
              case test
                    eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini \
                            --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf --test
              case '*'
                    echo "Usage: phpbrew fpm [start|stop|restart|module|test|help|config]"
            end

        case env
            # we don't check php path here, you should check path before you
            # use env command to output the environment config.
            [ (count $argv) -ge 2 ]; and set -gx PHPBREW_PHP $argv[2]

            echo "export PHPBREW_ROOT=$PHPBREW_ROOT";
            echo "export PHPBREW_HOME=$PHPBREW_HOME";

            if [ -n "$PHPBREW_LOOKUP_PREFIX" ]
                echo "export PHPBREW_LOOKUP_PREFIX=$PHPBREW_LOOKUP_PREFIX";
            end

            if [ -n "$PHPBREW_PHP" ]
                echo "export PHPBREW_PHP=$PHPBREW_PHP";
                echo "export PHPBREW_PATH=$PHPBREW_ROOT/php/$PHPBREW_PHP/bin";
            end

        case off
            set -e PHPBREW_PHP
            set -e PHPBREW_PATH
            eval (eval $BIN env)
            __phpbrew_set_path
            echo "phpbrew is turned off."

        case switch-off
            set -e PHPBREW_PHP
            set -e PHPBREW_PATH
            eval (eval $BIN env)
            __phpbrew_set_path
            __phpbrew_reinit
            echo "phpbrew is switched off."

        case rehash
            echo "Rehashing..."
            . ~/.phpbrew/phpbrew.fish

        case purge
            if [ (count $argv) -ge 2 ]
              __phpbrew_remove_purge $argv[2] purge
            else
                if [ $BIN = "phpbrew" ]
                    command phpbrew BIN help
                else
                    eval $BIN help
                end
            end

        case '*'
            if [ $BIN = "phpbrew" ]
                command phpbrew $short_option "$argv"
            else
                eval $BIN $short_option "$argv"
            end
            set exit_status $status
            ;;
    end
    hash -r
    return $exit_status
end

function __phpbrew_set_path
    functions --query php ; and functions -e php

    if [ -n "$PHPBREW_ROOT" ]
        begin; set -l NPATH;for i in $PATH; [ (expr "$i" : "$PHPBREW_ROOT") -eq 0 ]; and set NPATH $NPATH $i; end; set -gx PATH_WITHOUT_PHPBREW $NPATH;end;
    end

    if [ -z "$PHPBREW_PATH" ]
        set -gx PATH $PHPBREW_BIN $PATH_WITHOUT_PHPBREW
    else
      #set -gx PATH $PHPBREW_PATH $PHPBREW_BIN $PATH_WITHOUT_PHPBREW
        set -gx PATH $PHPBREW_PATH $PHPBREW_BIN $PATH_WITHOUT_PHPBREW
    end
    # echo "PATH => $PATH"
end

function __phpbrew_update_config
    set -l VERSION
    if begin; [ ! -d "$PHPBREW_ROOT/php/$argv" ]; and echo $argv | egrep -q -e $PHPBREW_VERSION_REGEX ; end
        set VERSION "php-$argv"
    else
        set VERSION $argv
    end
    echo '# DO NOT EDIT THIS FILE' > "$PHPBREW_HOME/init"
    if [ $BIN = "phpbrew" ]
        command phpbrew env $VERSION >> "$PHPBREW_HOME/init"
    else
        eval $BIN env $VERSION >> "$PHPBREW_HOME/init"
    end
    . "$PHPBREW_HOME/init"
end

function __phpbrew_reinit
    set -l _PHP_VERSION ""
    if [ (count $argv) -ge 1 ]
        set _PHP_VERSION $argv[1]
    end
    if [ ! -d "$PHPBREW_HOME" ]
        mkdir -p -p "$PHPBREW_HOME"
    end
    __phpbrew_update_config $_PHP_VERSION
    __phpbrew_set_path
end

function __phpbrew_remove_purge
    if [ (count $argv) -ge 1 ]
      set _PHP_VERSION $argv[1]
    end
    if [ "$_PHP_VERSION" = "$PHPBREW_PHP" ]
        echo "php version: $_PHP_VERSION is already in used."
        return 1
    end

    set _PHP_BIN_PATH $PHPBREW_ROOT/php/$_PHP_VERSION
    set _PHP_SOURCE_FILE $PHPBREW_ROOT/build/$_PHP_VERSION.tar.bz2
    set _PHP_BUILD_PATH $PHPBREW_ROOT/build/$_PHP_VERSION

    if [ -d $_PHP_BIN_PATH ]; then

        if begin; [ (count $argv) -ge 2 ]; and [ "$argv[2]" = "purge" ]; end
            rm -f $_PHP_SOURCE_FILE
            rm -fr $_PHP_BUILD_PATH
            rm -fr $_PHP_BIN_PATH
            echo "php version: $_PHP_VERSION is removed and purged."
        else
            rm -f $_PHP_SOURCE_FILE
            rm -fr $_PHP_BUILD_PATH

            for FILE1 in $_PHP_BIN_PATH/*
                if begin; [ "$FILE1" != "$_PHP_BIN_PATH/etc" ]; and [ "$FILE1" != "$_PHP_BIN_PATH/var" ]; end
                    rm -fr $FILE1
                end
            end

            echo "php version: $_PHP_VERSION is removed."
        end

    else
        echo "php version: $_PHP_VERSION not installed."
    end

    return 0
end

function phpbrew_current_php_version
  if type "php" > /dev/null
    set -l version (php -v | grep "PHP 5" | sed 's/.*PHP \([^-]*\).*/\1/' | cut -c 1-6)
    if [ -z "$PHPBREW_PHP" ]
      echo "php:$version-system"
    else
      echo "php:$version-phpbrew"
    end
  else
     echo "php:not-installed"
  end
end

if begin ; [ -n "$PHPBREW_SET_PROMPT" ]; and [ "$PHPBREW_SET_PROMPT" == "1" ]; end
    # export PS1="\w > \u@\h [$(phpbrew_current_php_version)]\n\\$ "
    # non supports in fish now
end

function _phpbrewrc_load
    # check if working dir has changed
    if [ "$PWD" != "$PHPBREW_LAST_DIR" ]
        set -l curr_dir "$PWD"
        set -l prev_dir "$OLDPWD"
        set -l curr_fs 0
        set -l prev_fs 0

        while true
            set prev_fs $curr_fs
            set curr_fs (stat -c %d . ^/dev/null)  # GNU version
            if [ $status -ne 0 ]; then
                set curr_fs (stat -f %d . ^>/dev/null)  # BSD version
            end

            # check if top level directory or filesystem boundary is reached
            if begin; [ "$PWD" == '/' ]; or [ -z "$PHPBREW_RC_DISCOVERY_ACROSS_FILESYSTEM" -a $prev_fs -ne 0 -a $curr_fs -ne $prev_fs ]; end
                set -e PHPBREW_LAST_RC_DIR
                __phpbrew_load_user_config
                break
            end

            # check if .phpbrewrc present
            if [ -r .phpbrewrc ]
                # check if it's not the same .phpbrewrc which was previously loaded
                if [ "$PWD" != "$PHPBREW_LAST_RC_DIR" ]
                    __phpbrew_load_user_config
                    set PHPBREW_LAST_RC_DIR "$PWD"
                    source .phpbrewrc
                end
                break
            end

            cd .. ^ /dev/null ; or break
        end

        cd "$curr_dir"
        set OLDPWD "$prev_dir"
    end

    set PHPBREW_LAST_DIR "$PWD"
end

if begin ; [ -n "$BASH_VERSION" ]; and [ -z "$PHPBREW_RC_DISABLE" ]; end
    trap "_phpbrewrc_load" DEBUG
end

exit 0

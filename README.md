PHPBrew
==========

phpbrew builds and installs multiple version php(s) in your $HOME directory.

phpbrew also manage the environment variables, so you can `use`, `switch` php
version whenever you need.

phpbrew can:

- build php with different variants like PDO, mysql, sqlite, debug ...etc.
- compile apache php module and seperate them by different versions.
- build and install php(s) in your home directory, so you don't need root permission.
- switch versions very easily and is integrated with bash/zsh shell.
- automatic feature detection.
- install multiple php into system-wide environment.

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/01.png"/>

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/03.png"/>


## Platform support

* Mac OS 10.5+
* Ubuntu
* Debian

## Requirement

* PHP5.3
* curl
* gcc, binutil, autoconf, libxml, zlib, readline

### Mac OS X Requirement

MacPorts users:

```bash
port install curl automake autoconf icu $(port echo depof:php5)
```

HomeBrew users:

```bash
brew install automake autoconf curl pcre re2c mhash libtool icu4c gettext jpeg libxml2 mcrypt gmp libevent
brew link icu4c
```

### Ubuntu/Debian Requirement

**Please note that you need to disable suhosin patch to run phpbrew.**

```bash
sudo apt-get install autoconf automake curl build-essential libxslt1-dev re2c libxml2-dev
sudo apt-get build-dep php5
```

### Cent OS Requirement

**Please note that you need to disable suhosin patch to run phpbrew.**

Cent OS requirement setup

```bash
sudo rpm -Uvh http://repo.webtatic.com/yum/centos/5/latest.rpm

# If you don't have php
sudo yum install --enablerepo=webtatic php php-xml
wget http://pkgs.repoforge.org/rpmforge-release/rpmforge-release-0.5.2-2.el5.rf.x86_64.rpm
sudo rpm -Uvh rpmforge-release-0.5.2-2.el5.rf.x86_64.rpm
sudo yum install --enablerepo=rpmforge re2c libmhash
```

Reference: http://matome.naver.jp/odai/2133887830324055901





## Install phpbrew

Just download it:

```bash
curl -O https://raw.github.com/c9s/phpbrew/master/phpbrew
chmod +x phpbrew
sudo cp phpbrew /usr/bin/phpbrew
```


## Basic usage

Init a bash script for your shell environment:

```bash
$ phpbrew init
```

Then add these lines to your `.bashrc` or `.zshrc` file:

```bash
$ source ~/.phpbrew/bashrc
```

To list known versions:

```bash
$ phpbrew known
Available stable versions:
    php-5.3.10
    php-5.3.9
    php-5.3.8
    php-5.3.7
```

To list known subversion versions:

```bash
$ phpbrew known --svn
```

To list known older versions (less than 5.3):

```bash
$ phpbrew known --old
```

## Build And Install

Simply build and install PHP with default variant:

```bash
$ phpbrew install php-5.4.0 +default
```

Here we suggest `default` variant set, which includes most commonly used
variants, if you need a minimum install, just remove the `default` variant set.


With tests:

```bash
$ phpbrew install --test php-5.4.0
```

With debug messages:

```bash
$ phpbrew -d install --test php-5.4.0
```



## Variants

PHPBrew arranges configure options for you, you can simply specify variant
name, and phpbrew will detect include paths and build options for configuring.

PHPBrew provides default variants and some virtual variants,
to the default variants, which includes the most commonly used variants,
to the virtual variants, which defines a variant set, you may use one virtual variant
to enable multiple variants at one time.

To check out what is included in these variants, simply run `variants`
subcommand to list these variants:

```bash
$ phpbrew variants
Variants:
  all, apxs2, bcmath, bz2, calendar, cgi, cli, ctype, dba, debug, dom, embed,
  exif, fileinfo, filter, fpm, ftp, gcov, gd, gettext, hash, iconv, icu,
  imap, intl, ipc, ipv6, json, kerberos, mbregex, mbstring, mcrypt, mhash,
  mysql, openssl, pcntl, pcre, pdo, pgsql, phar, posix, readline, session,
  soap, sockets, sqlite, tidy, tokenizer, xml_all, xmlrpc, zip, zlib


Virtual variants:
  dbs:      sqlite, mysql, pgsql, pdo
  mb:       mbstring, mbregex
  default:  filter, dom, bcmath, ctype, mhash, fileinfo, pdo, posix, ipc,
            pcntl, bz2, zip, cli, json, mbstring, mbregex, calendar, sockets, readline,
            xml_all

Using variants to build PHP:

  phpbrew install php-5.3.10 +default
  phpbrew install php-5.3.10 +mysql +pdo
  phpbrew install php-5.3.10 +mysql +pdo +apxs2
  phpbrew install php-5.3.10 +mysql +pdo +apxs2=/usr/bin/apxs2 
```

To enable one variant, simply add a prefix `+` before the variant name, eg

    +mysql

To disable one variant, simply add a prefix `-` before the variant name.

    -debug

For example, if we want to build PHP with the default options and 
database supports (mysql, sqlite, postgresql), you may simply run:

    $ phpbrew install php-5.4.5 +default+dbs

You may also build PHP with extra variants:

    $ phpbrew install php-5.3.10 +mysql+sqlite+cgi

    $ phpbrew install php-5.3.10 +mysql+debug+pgsql +apxs2

    $ phpbrew install php-5.3.10 +pdo +mysql +pgsql +apxs2=/usr/bin/apxs2

To build PHP with pgsql (Postgresql) extension:

    $ phpbrew install php-5.4.1 +pgsql+pdo

Or build pgsql extension with postgresql base dir:

    $ phpbrew install php-5.4.1 +pdo+pgsql=/opt/local/lib/postgresql91


NOTE:

> 1. If you want to build php with apache php module, please change the permission
> of apache module directory, eg: `/opt/local/apache2/modules/`.
> it should be writable and phpbrew should be able to change permission.
> after install, you should check your httpd.conf configuration file, to switch
> your php module version. :-)
>
> 2. phpbrew currently only supports for apxs2 (apache2)

If you enabled the `apxs2` variant, your apache conf file
might look like this if you have multiple php(s) installed
on your system:

    # LoadModule php5_module        modules/libphp5.3.10.so
    # LoadModule php5_module        modules/libphp5.4.0.so
    # LoadModule php5_module        modules/libphp5.4.0RC8.so
    # LoadModule php5_module        modules/libphp5.4.0RC7.so
    # LoadModule php5_module        modules/libphp5.4.1RC2.so
    # LoadModule php5_module        modules/libphp5.4.1.so
    # LoadModule php5_module        modules/libphp5.4.2.so
    LoadModule php5_module          modules/libphp5.4.4.so

You can simply uncomment/comment it to enable the php5
apache module you needed, after modifying it, remember
to restart your apache http server. :)

## Extra Configure Options

To pass extra configure arguments, you can do this:

    $ phpbrew install php-5.3.10 +mysql +sqlite -- \
        --enable-ftp --apxs2=/opt/local/apache2/bin/apxs

## Use And Switch

Use (switch version temporarily):

```bash
$ phpbrew use php-5.4.0RC7
```

Switch PHP version (switch default version)

```bash
$ phpbrew switch php-5.4.0
```

Turn Off:

```bash
$ phpbrew off
```

## List installed PHP

```bash
$ phpbrew list
```



## The Extension Installer

You can also install PHP extension with ease, either the extensions shipped
with PHP source code or even from PECL.

If the extension directory is found in PHP source, PHPBrew automatically switch into
the PHP source directory and install the extension.

If the extension directory is not found in PHP source, PHPBrew fetch the extension
package from PECL <http://pecl.php.net>.

PHPBrew also creates extension config to enable the installed extension, so you
don't need to write the config file to enable it by hands. The extension config
directory is in:

    ~/.phpbrew/php/php-{version}/var/db


### Installing Extension - The Most Simple Way

    phpbrew ext install APC
    phpbrew ext install memcache

### Installing Extension With Version

To install extensions with stability tag:

    phpbrew ext install xdebug stable
    phpbrew ext install xdebug latest
    phpbrew ext install xdebug beta

To install extensions with version name:

    phpbrew ext install xdebug 2.0.1

To install extensions with customized options:

    phpbrew ext install yaml -- --with-yaml=/opt/local

### Enabling Extension

You can also install extension via PECL and enable it manually:

    pecl install mongo
    phpbrew ext enable mongo

The `ext enable` command allows you to create a config {current php base}/var/db/{extension name}.ini
to enable the extension.

## Upgrade phpbrew

To upgrade phpbrew, you may simply run the `self-update` command,
this command enables you to install the latest version of
`master` branch from github:

    $ phpbrew self-update

## The Installed PHP(s)

The installed phps are located in `~/.phpbrew/php`, for example, php 5.4.0RC7 is located at:

    ~/.phpbrew/php/5.4.0RC7/bin/php

And you should put your configuration file in:

    ~/.phpbrew/php/5.4.0RC7/etc/php.ini

Extension configuration files should be put in:

    ~/.phpbrew/php/5.4.0RC7/var/db
    ~/.phpbrew/php/5.4.0RC7/var/db/xdebug.ini
    ~/.phpbrew/php/5.4.0RC7/var/db/apc.ini
    ~/.phpbrew/php/5.4.0RC7/var/db/memcache.ini
    ... etc

## Install phpbrew into system-wide environment

First, sudo as a root user or login as a root user:

    sudo -i

Now initialize your phpbrew bashrc for root:

    phpbrew init

Now export phpbrew paths to your desired paths, 
edit your ~/.phpbrew/init

    export PHPBREW_ROOT=/opt/phpbrew

Source your phpbrew bashrc

    source ~/.phpbrew/bashrc

Install system-wide php(s):

    phpbrew install php-5.4.5 +default +dbs

Now your php(s) will be installed under the /opt/phpbrew path,
To let your users can use php(s) built by phpbrew, you need to export 
`PHPBREW_ROOT` environment in /etc/bashrc or in /etc/profile.d/phpbrew for bash
users, before they load the phpbrew/bashrc file.

    export PHPBREW_ROOT=/opt/phpbrew
    source /opt/phpbrew/bashrc

To keep system's safety, please use `root` to install php(s).

a non-root user should not be able to install new php or switch 

and remember to fix permissions if these files 
were installed by non-root user.

    chown -R root: /opt/phpbrew


## Enable Version Info Prompt

To add PHP version info in your shell prompt, you can use
`"PHPBREW_SET_PROMPT=1"` variable.

The default is `"PHPBREW_SET_PROMPT=0"` (disable). To enable it, you can add this
line to your `~/.bashrc` file and put this line before you source
`~/.phpbrew/bashrc`.

```sh
    export PHPBREW_SET_PROMPT=1
```

To embed version info in your prompt, you can use
`current_php_version` shell function, which is defined in `.phpbrew/bashrc`.
and you can set the version info in your `PS1` var.
e.g.

```sh
    PHP_VERSION=$(current_php_version)
    PS1=" $PHP_VERSION \$ "
```


Known Issues
------------

- For PHP-5.3+ versions, "Building intl 64-bit fails on OS X" <https://bugs.php.net/bug.php?id=48795>

- To build PHP with GD extension, you need to specify your libpng dir and libjpeg dir, for example,

    $ phpbrew install php-5.4.10 +default +mysql +intl +gettext +apxs2=/usr/bin/apxs2 \
        -- --with-libdir=lib/x86_64-linux-gnu \
           --with-gd=shared \
           --enable-gd-natf \
           --with-jpeg-dir=/usr \
           --with-png-dir=/usr


Hacking
-------
Install Onion first:

    $ curl http://install.onionphp.org/ | sh

Install dependencies:

    $ onion -d install

Initialize

    $ php bin/phpbrew init

To hack with phpbrew shell script:

    $ vim phpbrew.sh

And then update the init script to the command class:

    $ ./scripts/update-init-script

List known versions:

    $ php bin/phpbrew known

Test your command through the `bin/phpbrew`:

    $ php bin/phpbrew -d install --no-test 5.4.0RC7

To show which phpbrew is running:

    $ unset -f phpbrew
    $ which phpbrew

Re-compile phar file for testing:

    $ scripts/compile

Add yourself to the contributor list in README.md

Then send pull request.

PHP Release channels
--------------------

- http://snaps.php.net/
- http://tw2.php.net/releases/
- http://downloads.php.net/stas/

Community
---------

Join us on #php-tw on irc.freenode.net

Contributors
------------

Sort alphabatically:

* gasol
* racklin
* yftzeng
* ujihisa


Author
------
Yo-An Lin (c9s)  <cornelius.howl@gmail.com>

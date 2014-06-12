PHPBrew
==========

phpbrew builds and installs multiple version php(s) in your $HOME directory.

phpbrew also manage the environment variables, so you can `use`, `switch` php
version whenever you need.

What phpbrew can do for you:

- Build php with different variants like PDO, mysql, sqlite, debug ...etc.
- Compile apache php module and separate them by different versions.
- Build and install php(s) in your home directory, so you don't need root permission.
- Switch versions very easily and is integrated with bash/zsh shell.
- Automatic feature detection.
- Install & enable php extensions into current environment with ease.
- Install multiple php into system-wide environment.

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/01.png"/>

## Requirement

Please see [Requirement](https://github.com/c9s/phpbrew/wiki/Requirement)
before you get started. you need to install some development packages for
building PHP.

## Install phpbrew

Just download it:

```bash
curl -L -O https://raw.github.com/c9s/phpbrew/master/phpbrew
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

You may setup your prefered default prefix for looking up libraries, available
options are `macports`, `homebrew`, `debian`, `ubuntu` or a custom path:

For Homebrew users:

```bash
$ phpbrew lookup-prefix homebrew
```

For Macports users:

```bash
$ phpbrew lookup-prefix macports
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
$ phpbrew install 5.4.0 +default
```

Here we suggest `default` variant set, which includes most commonly used
variants, if you need a minimum install, just remove the `default` variant set.


With tests:

```bash
$ phpbrew install --test 5.4.0
```

With debug messages:

```bash
$ phpbrew -d install --test 5.4.0
```

To install older versions (less than 5.3):

```bash
$ phpbrew install --old 5.2.13
```

## Clean up build


```bash
$ phpbrew clean
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
  neutral:
  default:  filter, dom, bcmath, ctype, mhash, fileinfo, pdo, posix, ipc,
            pcntl, bz2, zip, cli, json, mbstring, mbregex, calendar, sockets, readline,
            xml_all

Using variants to build PHP:

  phpbrew install 5.3.10 +default
  phpbrew install 5.3.10 +mysql +pdo
  phpbrew install 5.3.10 +mysql +pdo +apxs2
  phpbrew install 5.3.10 +mysql +pdo +apxs2=/usr/bin/apxs2 
```

To enable one variant, simply add a prefix `+` before the variant name, eg

    +mysql

To disable one variant, simply add a prefix `-` before the variant name.

    -debug

For example, if we want to build PHP with the default options and 
database supports (mysql, sqlite, postgresql), you may simply run:

```bash
$ phpbrew install 5.4.5 +default+dbs
```

You may also build PHP with extra variants:

```bash
$ phpbrew install 5.3.10 +mysql+sqlite+cgi

$ phpbrew install 5.3.10 +mysql+debug+pgsql +apxs2

$ phpbrew install 5.3.10 +pdo +mysql +pgsql +apxs2=/usr/bin/apxs2
```

To build PHP with pgsql (Postgresql) extension:

```bash
$ phpbrew install 5.4.1 +pgsql+pdo
```

Or build pgsql extension with postgresql base dir on Mac OS X:

```bash
$ phpbrew install 5.4.1 +pdo+pgsql=/opt/local/lib/postgresql91/bin
```

The pgsql path is the location of `pg_config`, you could find `pg_config` in the /opt/local/lib/postgresql91/bin


To build PHP with neutral compile options, you can specify `neutral` virtual variant, which means that phpbrew
doesn't add any additional compile options including `--disable-all`. But some options(for example `--enable-libxml`)
are still automatically added to support `pear`  installation.
You can build PHP with `neutral`:

```bash
$ phpbrew install 5.4.1 +neutral
```


For more details, please check out [PHPBrew Cookbook](https://github.com/c9s/phpbrew/wiki).


## Extra Configure Options

To pass extra configure arguments, you can do this:

```bash
$ phpbrew install 5.3.10 +mysql +sqlite -- \
    --enable-ftp --apxs2=/opt/local/apache2/bin/apxs
```

## Use And Switch

Use (switch version temporarily):

```bash
$ phpbrew use 5.4.22
```

Switch PHP version (switch default version)

```bash
$ phpbrew switch 5.4.18
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

```bash
$ phpbrew ext install APC
$ phpbrew ext install memcache
```

### Installing Extension With Version

To install extensions with stability tag:

```bash
$ phpbrew ext install xdebug stable
$ phpbrew ext install xdebug latest
$ phpbrew ext install xdebug beta
```

To install extensions with version name:

```bash
$ phpbrew ext install xdebug 2.0.1
```

To install extensions with customized options:

```bash
$ phpbrew ext install yaml -- --with-yaml=/opt/local
```

### Enabling Extension

You can also install extension via PECL and enable it manually:

```bash
pecl install mongo
phpbrew ext enable mongo
```

The `ext enable` command allows you to create a config {current php base}/var/db/{extension name}.ini
to enable the extension.


### Configuring the php.ini for current php version

Simply run:

```bash
$ phpbrew config
```

You may specify the EDITOR environment variable to your favorite editor:

```bash
export EDITOR=vim
phpbrew config
```

## Upgrade phpbrew

To upgrade phpbrew, you may simply run the `self-update` command,
this command enables you to install the latest version of
`master` branch from github:

```bash
$ phpbrew self-update
```

## The Installed PHP(s)

The installed phps are located in `~/.phpbrew/php`, for example, php 5.4.0RC7 is located at:

    ~/.phpbrew/php/5.4.20/bin/php

And you should put your configuration file in:

    ~/.phpbrew/php/5.4.20/etc/php.ini

Extension configuration files should be put in:

    ~/.phpbrew/php/5.4.20/var/db
    ~/.phpbrew/php/5.4.20/var/db/xdebug.ini
    ~/.phpbrew/php/5.4.20/var/db/apc.ini
    ~/.phpbrew/php/5.4.20/var/db/memcache.ini
    ... etc

## Quick commands to switch between directories

Switching to PHP build directory

```bash
$ phpbrew build-dir
```

Switching to PHP dist directory

```bash
$ phpbrew dist-dir
```

Switching to PHP etc directory

```bash
$ phpbrew etc-dir
```

Switching to PHP var directory

```bash
$ phpbrew var-dir
```

## PHP FPM

phpbrew also provides useful fpm managing sub-commands. to use them, please
remember to enable `+fpm` variant when building your own php.

To start php-fpm, simply type:

```bash
$ phpbrew fpm start
```

To stop php-fpm, type:

```bash
$ phpbrew fpm stop
```

To show php-fpm modules:

```bash
phpbrew fpm module
```

To test php-fpm config:

```bash
phpbrew fpm test
```

To edit php-fpm config:

```bash
phpbrew fpm config
```

> The installed `php-fpm` is located in `~/.phpbrew/php/php-*/sbin`.
> 
> The correspond `php-fpm.conf` is lcoated in `~/.phpbrew/php/php-*/etc/php-fpm.conf.default`,
> you may copy the default config file to the desired location. e.g.,
> 
>     cp -v ~/.phpbrew/php/php-*/etc/php-fpm.conf.default
>         ~/.phpbrew/php/php-*/etc/php-fpm.conf
> 
>     php-fpm --php-ini {php config file} --fpm-config {fpm config file}


## Installing Extra Component

### Installing composer 

```bash
$ phpbrew install-composer
```

### Installing phpunit

```bash
phpbrew install-phpunit
```

## Enabling Version Info Prompt

To add PHP version info in your shell prompt, you can use
`"PHPBREW_SET_PROMPT=1"` variable.

The default is `"PHPBREW_SET_PROMPT=0"` (disable). To enable it, you can add this
line to your `~/.bashrc` file and put this line before you source
`~/.phpbrew/bashrc`.

```bash
export PHPBREW_SET_PROMPT=1
```

To embed version info in your prompt, you can use
`current_php_version` shell function, which is defined in `.phpbrew/bashrc`.
and you can set the version info in your `PS1` var.
e.g.

```bash
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




Troubleshooting
-------------------
Please see [TroubleShooting](https://github.com/c9s/phpbrew/wiki/TroubleShooting)

FAQ
-------------------------

Q: How do I have the same version with different compile option?

A: Currently, you can install php5.x.x and rename the /Users/c9s/.phpbrew/php/php-5.x.x folder to the new name, for example, php-5.x.x-super , and install another php-5.3.3



Contribution
------------------
Please see [Contribution](https://github.com/c9s/phpbrew/wiki/Contribution)


Documentation
-------------
Please see [Wiki](https://github.com/c9s/phpbrew/wiki)


Community
---------

Join us on #php-tw on irc.freenode.net

Author
------

Yo-An Lin (c9s)  <cornelius.howl _at_ gmail.com>

License
--------

See LICENSE file.


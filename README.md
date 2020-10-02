PHPBrew
=======

*Read this in other languages:  [English](README.md), [Português - BR](README.pt-br.md), [日本語](README.ja.md), [中文](README.cn.md).*

[![Build Status](https://travis-ci.org/phpbrew/phpbrew.svg?branch=master)](https://travis-ci.org/phpbrew/phpbrew)
[![Coverage Status](https://img.shields.io/coveralls/phpbrew/phpbrew.svg)](https://coveralls.io/r/phpbrew/phpbrew)
[![Gitter](https://badges.gitter.im/phpbrew/phpbrew.svg)](https://gitter.im/phpbrew/phpbrew?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

phpbrew builds and installs multiple version php(s) in your $HOME directory.

What phpbrew can do for you:

- Configure options are simplified into variants,  no worries about the path anymore!
- Build php with different variants like PDO, mysql, sqlite, debug ...etc.
- Compile apache php module and separate them by different versions.
- Build and install php(s) in your home directory, so you don't need root permission.
- Switch versions very easily and is integrated with bash/zsh shell.
- Automatic feature detection.
- Install & enable php extensions into current environment with ease.
- Install multiple php into system-wide environment.
- Path detection optimization for HomeBrew and MacPorts.

<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/01.png"/>
<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/03.png"/>

## Requirement

Please see [Requirement](https://github.com/phpbrew/phpbrew/wiki/Requirement)
before you get started. you need to install some development packages for
building PHP.

## QUICK START

Please see [Quick Start](https://github.com/phpbrew/phpbrew/wiki/Quick-Start) if you're impatient. :-p

## GETTING STARTED

OK, I assume you have more time to work on this, this is a step-by-step
tutorial that helps you get started.

### Installation

```bash
curl -L -O https://github.com/phpbrew/phpbrew/releases/latest/download/phpbrew.phar
chmod +x phpbrew.phar

# Move the file to some directory within your $PATH
sudo mv phpbrew.phar /usr/local/bin/phpbrew
```

Init a bash script for your shell environment:

```bash
phpbrew init
```

Add these lines to your `.bashrc` or `.zshrc` file:

```bash
[[ -e ~/.phpbrew/bashrc ]] && source ~/.phpbrew/bashrc
```

For fish shell users, add following lines to your `~/.config/fish/config.fish` file:

```fish
source ~/.phpbrew/phpbrew.fish
```

If you're using system-wide phpbrew, you may setup a shared phpbrew root, for example:

```bash
mkdir -p /opt/phpbrew
phpbrew init --root=/opt/phpbrew
```

### Setting up lookup prefix

You may setup your preferred default prefix for looking up libraries, available
options are `macports`, `homebrew`, `debian`, `ubuntu` or a custom path:

For Homebrew users:

```bash
phpbrew lookup-prefix homebrew
```

For Macports users:

```bash
phpbrew lookup-prefix macports
```

## Basic usage

To list known versions:

```bash
phpbrew known

7.0: 7.0.3, 7.0.2, 7.0.1, 7.0.0 ...
5.6: 5.6.18, 5.6.17, 5.6.16, 5.6.15, 5.6.14, 5.6.13, 5.6.12, 5.6.11 ...
5.5: 5.5.32, 5.5.31, 5.5.30, 5.5.29, 5.5.28, 5.5.27, 5.5.26, 5.5.25 ...
5.4: 5.4.45, 5.4.44, 5.4.43, 5.4.42, 5.4.41, 5.4.40, 5.4.39, 5.4.38 ...
5.3: 5.3.29, 5.3.28 ...
```

To show more minor versions:

```bash
$ phpbrew known --more
```

To update the release info:

```bash
$ phpbrew update
```

To get older versions (less than 5.4)

> Please note that we don't guarantee that you can build the php versions that
> are not supported by offical successfully, please don't fire any issue about
> the older versions, these issues won't be fixed.

```bash
$ phpbrew update --old
```

To list known older versions (less than 5.4)

```bash
$ phpbrew known --old
```

## Starting Building Your Own PHP

Simply build and install PHP with default variant:

```bash
$ phpbrew install 5.4.0 +default
```

Here we suggest `default` variant set, which includes most commonly used
variants, if you need a minimum install, just remove the `default` variant set.

You can enable parallel compilation by passing `-j` or `--jobs` option and
the following is an example:

```bash
$ phpbrew install -j $(nproc) 5.4.0 +default
```

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

To install the latest patch version of a given release:

```bash
$ phpbrew install 5.6
```

To install a pre-release version:

```bash
$ phpbrew install 7.2.0alpha1
$ phpbrew install 7.2.0beta2
$ phpbrew install 7.2.0RC3
```

To install from a GitHub tag or branch name:

```bash
$ phpbrew install github:php/php-src@PHP-7.2 as php-7.2.0-dev
```

To install the next (unstable) version:

```bash
$ phpbrew install next as php-7.3.0-dev
```

## Cleaning up build directory

```bash
$ phpbrew clean php-5.4.0
```

## Variants

PHPBrew arranges configure options for you, you can simply specify variant
name, and phpbrew will detect include paths and build options for configuring.

PHPBrew provides default variants and some virtual variants,
to the default variants, which includes the most commonly used variants,
to the virtual variants, which defines a variant set, you may use one virtual variant
to enable multiple variants at one time.

To check out what is included in these variants, run `phpbrew variants`
to list these variants.

To enable one variant, add a prefix `+` before the variant name, eg

    +mysql

To disable one variant, add a prefix `-` before the variant name.

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

To build PHP with pgsql (PostgreSQL) extension:

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
are still automatically added to support `pear` installation.
You can build PHP with `neutral`:

```bash
$ phpbrew install 5.4.1 +neutral
```

For more details, please check out [PHPBrew Cookbook](https://github.com/phpbrew/phpbrew/wiki).

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

If you enable apache PHP modules, remember to comment out or remove the settings.

```bash
$ sudo vim /etc/httpd/conf/httpd.conf
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.21.so
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.20.so
```

## The Extension Installer

See [Extension Installer](https://github.com/phpbrew/phpbrew/wiki/Extension-Installer)

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
`master` branch from GitHub:

```bash
$ phpbrew self-update
```

## The Installed PHP(s)

To list all installed php(s), you could run:

```bash
$ phpbrew list
```

The installed phps are located in `~/.phpbrew/php`, for example, php 5.4.20 is located at:

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
> The correspond `php-fpm.conf` is located in `~/.phpbrew/php/php-*/etc/php-fpm.conf.default`,
> you may copy the default config file to the desired location. e.g.,
>
>     cp -v ~/.phpbrew/php/php-*/etc/php-fpm.conf.default
>         ~/.phpbrew/php/php-*/etc/php-fpm.conf
>
>     php-fpm --php-ini {php config file} --fpm-config {fpm config file}

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
`phpbrew_current_php_version` shell function, which is defined in `.phpbrew/bashrc`.
and you can set the version info in your `PS1` var.
e.g.

```bash
PS1=" \$(phpbrew_current_php_version) \$ "
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
Please see [TroubleShooting](https://github.com/phpbrew/phpbrew/wiki/TroubleShooting)

FAQ
-------------------------

Q: How do I have the same version with different compile option?

A: Currently, you can install php-5.x.x and rename the /Users/phpbrew/.phpbrew/php/php-5.x.x folder to the new name, for example, php-5.x.x-super , and install another php-5.x.x



Contribution
------------------
Please see [Contribution](https://github.com/phpbrew/phpbrew/wiki/Contribution)


Documentation
-------------
Please see [Wiki](https://github.com/phpbrew/phpbrew/wiki)



Author
------

- Yo-An Lin (c9s)  <yoanlin93 _at_ gmail.com>
- Márcio Almad <marcio3w _at_ gmail.com>

License
--------

See [LICENSE](LICENSE) file.



[t-link]: https://travis-ci.org/phpbrew/phpbrew "Travis Build"
[s-link]: https://scrutinizer-ci.com/g/phpbrew/phpbrew/?branch=master "Code Quality"
[p-link]: https://packagist.org/packages/marc/phpbrew "Packagist"
[sl-link]: https://insight.sensiolabs.com/projects/02d1fd01-8a70-4fe4-a550-381a3c0e33f3 "Sensiolabs Insight"
[c-badge]: https://coveralls.io/repos/phpbrew/phpbrew/badge.png?branch=master

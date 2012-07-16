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

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/01.png"/>

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/03.png"/>


## Platform support

* Mac OS 10.5+
* Ubuntu, Debian

## Requirement

* PHP5.3
* curl
* gcc, binutil, autoconf, libxml, zlib, readline

### Mac OS X Requirement

MacPorts users:

```bash
port install curl automake autoconf $(port echo depof:php5)
```

HomeBrew users:

```bash
brew install automake autoconf curl pcre mhash glibtool

# incomplete
```

### Ubuntu/Debian Requirement

```bash
sudo apt-get install autoconf automake curl build-essential libxslt1-dev libtidy-dev re2c libxml2-dev
sudo apt-get build-dep php5 
```

### Cent OS Requirement

Cent OS requirement setup

```bash
sudo rpm -Uvh http://repo.webtatic.com/yum/centos/5/latest.rpm

# If you don't have php
sudo yum install --enablerepo=webtatic php php-xml libtidy
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
Available svn versions:
    php-svn-head
    php-svn-5.3
    php-svn-5.4
Available versions from PhpStas:
    php-5.4.0RC1
    php-5.4.0RC2
    ....
    php-5.4.0RC7
    ....
    php-5.4.0alpha3
    php-5.4.0beta2
```


## Build And Install

simply build and install PHP:

```bash
$ phpbrew install php-5.4.0
```

With tests:

```bash
$ phpbrew install --test php-5.4.0
```

With debug messages:

```bash
$ phpbrew -d install --test php-5.4.0
```





## Variants

To list variants:

```bash
$ phpbrew variants

Variants:
    pear
    mysql
    debug
    sqlite
    pgsql
    cli
    apxs2
    cgi
    soap
    pcntl
    ... (etc)
```

You can build PHP with extra variants:

    $ phpbrew install php-5.3.10 +mysql+sqlite+cgi

    $ phpbrew install php-5.3.10 +mysql+debug+pgsql +apxs2

    $ phpbrew install php-5.3.10 +mysql +pgsql +apxs2=/usr/bin/apxs2

PDO is enabled by default.

To build PHP with pgsql (Postgresql) extension:

    $ phpbrew install php-5.4.1 +pgsql

Or build pgsql extension with postgresql base dir:

    $ phpbrew install php-5.4.1 +pgsql=/opt/local/lib/postgresql91 


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

## Extra options

To pass extra configure arguments, you can do this:

    $ phpbrew install php-5.3.10 +mysql +sqlite -- \
      --enable-ftp --apxs2=/opt/local/apache2/bin/apxs

## Use And Switch

Use (switch version temporarily):

```bash
$ phpbrew use php-5.4.0RC7
```

Switch (switch version as default)

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

## Build & Install extensions from PHP source 

(after the installation):

    phpbrew install-ext pdo
    phpbrew install-ext mcrypt --with-mcrypt=/opt/local

## Enable Extension

    pecl install mongo
    phpbrew enable mongo

the `enable` command allows you to create a config {current php base}/var/db/{extension name}.ini 
to enable the extension.

## Upgrade phpbrew

To upgrade phpbrew, you can simply run the `self-update` command,
this command enables you to install the latest version of
`master` branch from github:

    $ phpbrew self-update

## The Installed PHPs

The installed phps are located in `~/.phpbrew/php`, for example, php 5.4.0RC7 is located at:

    ~/.phpbrew/php/5.4.0RC7/bin/php

And you should put your configuration file in:

    ~/.phpbrew/php/5.4.0RC7/etc/php.ini

Extension configuration files should be put in:

    ~/.phpbrew/php/5.4.0RC7/var/db


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

Hacking
-------
Install Onion first:

    $ curl http://install.onionphp.org/ | sh

Install dependencies:

    $ onion -d install

Initialize

    $ php bin/phpbrew init

List known versions:

    $ php bin/phpbrew known

Install:

    $ php bin/phpbrew -d install --no-test 5.4.0RC7

To show which phpbrew is running:

    $ unset -f phpbrew
    $ which phpbrew

Re-compile phar file:

    $ bash scripts/compile.sh

Community
---------

Join us on #php-tw on irc.freenode.net

Contributors
------------

* yftzeng 
* Gasol

Author
------
Yo-An Lin (c9s)  <cornelius.howl@gmail.com>

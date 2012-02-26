PhpBrew
==========

phpbrew builds and installs multiple version php(s) in your $HOME directory.

phpbrew also manage the environment variables, so you can `use`, `switch` php
version whenever you need.

phpbrew can:

- build php with different variants like PDO, mysql, sqlite, debug ...etc.
- compile apache php module and seperate them by different versions.
- build and install php(s) in your home directory, so you don't need root permission.
- switch versions very easily and is integrated with bash/zsh shell.

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/01.png"/>

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/03.png"/>


## Platform support

* Mac OS 10.7
* Ubuntu, Debian

## Requirements

* PHP5.3
* curl
* gcc, binutil, autoconf, libxml, zlib, readline

For Ubuntu/Debian users:

    $ sudo apt-get build-dep php5

## Install phpbrew

    $ sudo pear channel-discover pear.corneltek.com
    $ sudo pear install -a -f corneltek/PhpBrew

Install from github:

    $ git clone git@github.com:c9s/phpbrew.git
    $ cd phpbrew
    $ sudo pear install -f package.xml

## Basic usage

Init a bash script for your shell environment:

    $ phpbrew init

Then add these lines to your `.bashrc` or `.zshrc` file:

    source ~/.phpbrew/bashrc


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
	php-5.4.0RC3
	php-5.4.0RC4
	php-5.4.0RC5
	php-5.4.0RC6
	php-5.4.0RC7
	php-5.4.0alpha1
	php-5.4.0alpha2
	php-5.4.0alpha3
	php-5.4.0beta1
	php-5.4.0beta2
```

To list variants:

```bash
$ phpbrew variants

Variants:
    pdo
    pear
    mysql
    sqlite
    cli
    apxs2
    debug
    cgi
    soap
    pcntl
```

You can build PHP with extra variants:

    phpbrew install php-5.3.10 +mysql +pdo +pear +sqlite +cgi
    phpbrew install php-5.3.10 +mysql +pdo +pear +apxs2
    phpbrew install php-5.3.10 +mysql +pdo +pear +apxs2=/usr/bin/apxs2

NOTE:

> 1. If you want to build php with apache php module, please change the permission
> of apache module directory, eg: `/opt/local/apache2/modules/`.  
> it should be writable and phpbrew should be able to change permission.
> after install, you should check your httpd.conf configuration file, to switch 
> your php module version. :-)
> 
> 2. phpbrew currently only supports for apxs2 (apache2)

Or simply build and install PHP:

```bash
$ phpbrew install php-5.4.0RC7
```

Without tests:

```bash
$ phpbrew install --no-test php-5.4.0RC7
```

With debug messages:

```bash
$ phpbrew -d install --no-test php-5.4.0RC7
```

Use (switch version temporarily):

```bash
$ phpbrew use php-5.4.0RC7
```

Turn Off:

```bash
$ phpbrew off 
```

List installed PHP:

```bash
$ phpbrew list
```

## Installed PHPs

The installed phps are located in `~/.phpbrew/php`, for example, php 5.4.0RC7 is located at:

    ~/.phpbrew/php/5.4.0RC7/bin/php

And you should put your configuration file in:

    ~/.phpbrew/php/5.4.0RC7/etc/php.ini

Extension configuration files should be put in:

    ~/.phpbrew/php/5.4.0RC7/var/db


Hacking
-------
Install Onion first:

    $ curl http://install.onionphp.org/ | sh

Install dependencies:

    $ onion -d bundle

Initialize

    php scripts/phpbrew.php init

List known versions:

    php scripts/phpbrew.php known

Install:

    php scripts/phpbrew.php -d install --no-test 5.4.0RC7

Community
---------

Join us on #php-tw on irc.freenode.net


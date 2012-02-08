PhpBrew
==========

phpbrew builds and installs multiple version php(s) in your $HOME directory.

phpbrew also manage the environment variables, so you can `use`, `swtich` php
version whenever you need.


The installed php is located in `~/.phpbrew/php`, for example, php is located at:

    ~/.phpbrew/php/5.4.0RC7/bin/php

And you should put your configuration file in:

    ~/.phpbrew/php/5.4.0RC7/etc/php.ini

Extension configuration files should be put in:

    ~/.phpbrew/php/5.4.0RC7/var/db


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
    $ sudo pear install corneltek/PhpBrew

Install from github:

    $ git clone git@github.com:c9s/phpbrew.git
    $ cd phpbrew
    $ sudo pear install -f package.xml

## Basic usage

Init:

    $ phpbrew init

List known versions:

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

Build and install PHP:

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

Use:

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

PhpBrew
==========

## Install

    $ sudo pear channel-discover pear.corneltek.com
    $ sudo pear install corneltek/PhpBrew

Install from github:

    $ git clone git@github.com:c9s/phpbrew.git
    $ cd phpbrew
    $ sudo pear install -f package.xml

## Usage

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
$ phpbrew install --no-test 5.4.0RC7
```

Use:

```bash
$ phpbrew use 5.4.0RC7
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

Install bundle:

    $ onion -d bundle

List known versions:

    php scripts/phpbrew.php known

Install:

    php scripts/phpbrew.php install --no-test 5.4.0RC7

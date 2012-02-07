PhpBrew
==========

List known versions:

```bash
$ php scripts/phpbrew.php known
Available versions from PhpStas:
	5.4.0RC1
	5.4.0RC2
	5.4.0RC3
	5.4.0RC4
	5.4.0RC5
	5.4.0RC6
	5.4.0RC7
	5.4.0alpha1
	5.4.0alpha2
	5.4.0alpha3
	5.4.0beta1
	5.4.0beta2
Available svn versions:
	php-svn-head
	php-svn-5.3
	php-svn-5.4
```

Build and install PHP:

```bash
$ php scripts/phpbrew.php install --no-test 5.4.0RC7
```


Hacking
-------

Install bundle:

    $ onion -d bundle

List known versions:

    php scripts/phpbrew.php known

Install:

    php scripts/phpbrew.php install --no-test 5.4.0RC7

PHPBrew
=======

*Leia este documento em outros idiomas: [English](README.md), [Português - BR](README.pt-br.md), [日本語](README.ja.md).*

[![Build Status](https://travis-ci.org/phpbrew/phpbrew.svg?branch=master)](https://travis-ci.org/phpbrew/phpbrew)
[![Coverage Status](https://img.shields.io/coveralls/phpbrew/phpbrew.svg)](https://coveralls.io/r/phpbrew/phpbrew)

phpbrew contrói e instala multiplas versões do php no seu diretório $HOME.

O que phpbrew pode fazer para você:

- As configurações são feitas dentro de variaveis, não se preocupe mais com caminhos.
- Construa php com diferentes variaveis como PDO, mysql, sqlite, debug etc...  
- Compile modulos php do apache e separe eles por diferentes versões.
- Construa e instale php(s) no seu diretório home, assim não irá precisar de permissão de root.
- Troque de versões muito facilmente pois é integrado com bash/zsh shell.
- Detecção automática de recurso.
- Instale & abilite extenssões php no ambiente atual com facilidade.
- Instale multiplas versões php em todo ambiente do sistema.
- Detecção de caminho otimizado para HomeBrew e MacPorts.

<img width="600" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/01.png"/>

## Requisitos

Por favor veja [Requisitos](https://github.com/phpbrew/phpbrew/wiki/Requirement)
antes de você começar. Você precisa instalar alguns pacotes de desenvolvimentos para
desenvolvimento php.

## Instale phpbrew

Faça isso:

```bash
curl -L -O https://github.com/phpbrew/phpbrew/raw/master/phpbrew
chmod +x phpbrew
```

Instale dento da pasta bin do seu sistema:

```sh
sudo mv phpbrew /usr/local/bin/phpbrew
```

Confirme se `/usr/local/bin` está nas `$PATH` variaveis de ambiente do sistema.


## Configurando

Inicie um script no seu ambiente (Terminal) shell

```bash
$ phpbrew init
```

Então adicione essas linhas para seu arquivo `.bashrc` ou `.zshrc` ou `.bash_profile`:

```bash
[[ -e ~/.phpbrew/bashrc ]] && source ~/.phpbrew/bashrc
```

### Configurar prefixo de pesquisa

Você pode configurar seus prefixos de preferências padrões para procurar bibliotecas,
as opções disponivéis são `macports`, `homebrew`, `debian`, `ubuntu` ou um caminho customizado:

Para usuários Homebrew:

```bash
$ phpbrew lookup-prefix homebrew
```

Para usuários Macports:

```bash
$ phpbrew lookup-prefix macports
```

## Uso básico

Para listar versões conhecidas:

```bash
$ phpbrew known
7.0: 7.0.3, 7.0.2, 7.0.1, 7.0.0 ...
5.6: 5.6.18, 5.6.17, 5.6.16, 5.6.15, 5.6.14, 5.6.13, 5.6.12, 5.6.11 ...
5.5: 5.5.32, 5.5.31, 5.5.30, 5.5.29, 5.5.28, 5.5.27, 5.5.26, 5.5.25 ...
5.4: 5.4.45, 5.4.44, 5.4.43, 5.4.42, 5.4.41, 5.4.40, 5.4.39, 5.4.38 ...
5.3: 5.3.29, 5.3.28 ...
```

Para listar versões antigas conhecidas (menor que 5.3):

```bash
$ phpbrew known --old
```

Para atualizar a informações de release:

```bash
$ phpbrew update
```

## Contruir e instalar

Simplesmente construa e instale php com a variante padrão:

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

To install the next (unstable) version:

```bash
$ phpbrew install next as php-7.1.0
```

To install from a github tag:
```bash
$ phpbrew install github:php/php-src@PHP-7.0 as php-7.0.0
```

## Clean up build


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

To check out what is included in these variants, simply run `variants`
subcommand to list these variants:

```bash
$ phpbrew variants

Variants:
  all, apxs2, bcmath, bz2, calendar, cgi, cli, ctype, curl, dba, debug, dom,
  dtrace, editline, embed, exif, fileinfo, filter, fpm, ftp, gcov, gd,
  gettext, gmp, hash, iconv, icu, imap, inifile, inline, intl, ipc, ipv6,
  json, kerberos, libgcc, mbregex, mbstring, mcrypt, mhash, mysql, opcache,
  openssl, pcntl, pcre, pdo, pgsql, phar, phpdbg, posix, readline, session,
  soap, sockets, sqlite, static, tidy, tokenizer, wddx, xml, xml_all, xmlrpc,
  zip, zlib, zts


Virtual variants:
  dbs:        sqlite, mysql, pgsql, pdo

  mb:         mbstring, mbregex

  neutral:

  small:      bz2, cli, dom, filter, ipc, json, mbregex, mbstring, pcre, phar,
              posix, readline, xml, curl, openssl

  default:    bcmath, bz2, calendar, cli, ctype, dom, fileinfo, filter, ipc,
              json, mbregex, mbstring, mhash, mcrypt, pcntl, pcre, pdo, phar,
              posix, readline, sockets, tokenizer, xml, curl, openssl, zip

  everything: dba, ipv6, dom, calendar, wddx, static, inifile, inline, cli,
              ftp, filter, gcov, zts, json, hash, exif, mbstring, mbregex,
              libgcc, pdo, posix, embed, sockets, debug, phpdbg, zip, bcmath,
              fileinfo, ctype, cgi, soap, pcntl, phar, session, tokenizer,
              opcache, imap, tidy, kerberos, xmlrpc, fpm, dtrace, pcre, mhash,
              mcrypt, zlib, curl, readline, editline, gd, intl, icu, openssl,
              mysql, sqlite, pgsql, xml, xml_all, gettext, iconv, bz2, ipc, gmp


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
are still automatically added to support `pear`  installation.
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
`master` branch from GitHub:

```bash
$ phpbrew self-update
```

## The Installed PHP(s)

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
> The correspond `php-fpm.conf` is lcoated in `~/.phpbrew/php/php-*/etc/php-fpm.conf.default`,
> you may copy the default config file to the desired location. e.g.,
>
>     cp -v ~/.phpbrew/php/php-*/etc/php-fpm.conf.default
>         ~/.phpbrew/php/php-*/etc/php-fpm.conf
>
>     php-fpm --php-ini {php config file} --fpm-config {fpm config file}


## Installing Extra Apps

phpbrew provides app command to fetch some php apps.

### Installing composer

```bash
$ phpbrew app get composer
```

### Installing phpunit

```bash
phpbrew app get phpunit
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

A: Currently, you can install php5.x.x and rename the /Users/phpbrew/.phpbrew/php/php-5.x.x folder to the new name, for example, php-5.x.x-super , and install another php-5.3.3



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

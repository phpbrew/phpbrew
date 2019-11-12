<?php

/**
Please DO NOT modify this file directly.
*/

namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class CookbookTopic extends GitHubTopic
{
    public $id = 'cookbook';
    public $url = 'https://github.com/phpbrew/phpbrew/wiki/Cookbook.md';
    public $title = 'Cookbook';

    public function getRemoteUrl()
    {
        return $this->remoteUrl;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getContent()
    {
        return '### Setting up prefered lookup prefix

phpbrew automatically detects the library prefix and include prefix for each different variant. for example, 
if you want to include gd extension support, phpbrew finds if `gd2.h` exists in your include prefix.

Now you can specify your prefered lookup prefix for HomeBrew, Macports, Linux
or setup your customized prefix for the library prefix auto-lookup.

If you\'re using HomeBrew, you can simply type:

```
phpbrew lookup-prefix homebrew
```

After you setup the lookup prefix, phpbrew will do all the stuff automatically.

let\'s take the `+gd` variant as an example, when you install php with `+gd`
variant, phpbrew expands the options to:

    --with-gd=/usr/local/Cellar \\
        --with-png-dir=/usr/local/Cellar \\
        --with-jpeg-dir=/usr/local/Cellar \\
        --with-freetype-dir=/usr/local/Cellar  \\
        --enable-gd-native-ttf

If you\'re using Macports, simply type:

```
phpbrew lookup-prefix macports
```

Then the same variant above can be expanded to:

    --with-gd=/opt/local --with-png-dir=/opt/local \\
        --with-jpeg-dir=/opt/local \\
        --with-freetype-dir=/opt/local  \\
        --enable-gd-native-ttf

You may also specify your custom prefix by typing:

```
phpbrew lookup-prefix ~/local
```

Again, the options can be expanded to:

    --with-gd=~/local --with-png-dir=~/local \\
        --with-jpeg-dir=~/local \\
        --with-freetype-dir=~/local  \\
        --enable-gd-native-ttf

### GD support

To compile php with gd library support:

    phpbrew install 5.4.12 +gd

This will detect paths of freetype, libpng, libjpeg library and expand to the options below:

    --with-gd=$prefix --with-png-dir=$prefix --with-jpeg-dir=$prefix --with-freetype-dir=/opt/local --enable-gd-native-ttf

If it shows references not found, you may speicify +gd variant with `shared` value. e.g.,

    phpbrew install 5.4.12 +gd=shared

The command above compiles your gd extension into gd.so (shared library)

### Apache2 support

To compile php with apache2 SAPI support:

    phpbrew install 5.4.12 +apxs2

This will find your `apxs2` binary automatically.
If you have different `apxs2` under different paths on your system, you may
speicify apxs2 with the full path, for example if you need to compile your php with apache2 support (installed by Macports):

    phpbrew install 5.4.12 +apxs2=/opt/local/apache2/bin/apxs

This will patch the `configure` script to let you install the php module with
different version.

But you still have to modify the config file manually if you need to change the
php version.

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


### OpenSSL support

If you want to compile php with openssl support, you just add the `openssl` variant

    phpbrew install 5.4.12 +openssl

And phpbrew will find your openssl include path automatically.

If you need to compile with speicific openssl library, simply add the path after the openssl variant:

    phpbrew install 5.4.12 +openssl=/opt/local

If you need to compile the openssl as a shared library:

    phpbrew install 5.4.12 +openssl=shared

### Compile php with extra options

If you want to use extra configure options, which are not included in phpbrew variants,
You may append a `--` double dash to separate your custom options, e.g.,

    phpbrew install 5.4.12 -- --with-freetype-dir=/opt/local --with-png-dir=/opt/local


### Install phpbrew into system-wide environment

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

To keep system\'s safety, please use `root` to install php(s).

a non-root user should not be able to install new php or switch 

and remember to fix permissions if these files 
were installed by non-root user.

    chown -R root: /opt/phpbrew

### OPCache Support

Compile php with OPCache support:

```bash
phpbrew install 5.5.5 +opcache
```

### How do I apply a patch before building my PHP?

Simply specify the option to apply a patch:

    phpbrew install --patch [File Path] 5.4.22

Please see the related issue here: https://github.com/c9s/phpbrew/issues/152

### How do I install libevent extension?

```bash
phpbrew ext install libevent latest -- --with-libevent=/opt/local
```


';
    }
}

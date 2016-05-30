<?php
/**
Please DO NOT modify this file directly.
*/

namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class MigratingFromHomebrewPhpToPhpbrewTopic  extends GitHubTopic
{
    public $id = 'migrating-from-homebrew-php-to-phpbrew';
    public $url = 'https://github.com/phpbrew/phpbrew/wiki/Migrating-from-homebrew-php-to-phpbrew.md';
    public $title = 'Migrating from homebrew php to phpbrew';

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
        return 'Written by Raphael Stolt [original gist](https://gist.github.com/raphaelstolt/5988689)

## Migrating from [homebrew-php](https://github.com/josegonzalez/homebrew-php) to [phpbrew](https://github.com/c9s/phpbrew)

After installing [phpbrew](https://github.com/c9s/phpbrew#install-phpbrew) it\'s time to install a set of chosen PHP versions. My picks at the time of this writing were:

    sudo phpbrew install php-5.5.0 +default+dbs+mb+apxs2=/usr/sbin/apxs
    sudo phpbrew install php-5.4.17 +default+dbs+mb+apxs2=/usr/sbin/apxs
    sudo phpbrew install php-5.3.27 +default+dbs+mb+apxs2=/usr/sbin/apxs

When not sure where apxs is located on your system, `whereis apxs` is quite chatty.

After the installations succeeded the picked PHP versions should be visible via the `phpbrew list` command.

    phpbrew list
    Installed versions:
      php-5.3.27       (/Users/stolt/.phpbrew/php/php-5.3.27)
                       +default+dbs+mb+apxs2=/usr/sbin/apxs
      php-5.4.17       (/Users/stolt/.phpbrew/php/php-5.4.17)
                       +default+dbs+mb+apxs2=/usr/sbin/apxs
      php-5.5.0        (/Users/stolt/.phpbrew/php/php-5.5.0)
                       +default+dbs+mb+apxs2=/usr/sbin/apxs

Extensions and PEAR packages (i.e. PHPUnit which might also be installed project specific via [Composer](http://getcomposer.org)) for PHP 5.5.0

    phpbrew switch php-5.5.0
    php -v 
        PHP 5.5.0 (cli) (built: Jul 12 2013 22:55:52)
        Copyright (c) 1997-2013 The PHP Group
        Zend Engine v2.5.0-dev, Copyright (c) 1998-2013 Zend Technologies
    
    sudo phpbrew ext install redis && sudo phpbrew ext enable redis
    sudo phpbrew ext install xdebug && sudo phpbrew ext enable xdebug
    
    php -m | grep xdebug && php -m | grep redis
        xdebug
        redis
    
    sudo pear config-set auto_discover 1
    sudo pear install pear.phpunit.de/PHPUnit
    
    which phpunit
        /Users/<username>/.phpbrew/php/php-5.5.0/bin/phpunit

Extensions and PEAR packages for PHP 5.4.17

    phpbrew switch php-5.4.17
    php -v
        PHP 5.4.17 (cli) (built: Jul 12 2013 22:46:15)
        Copyright (c) 1997-2013 The PHP Group
        Zend Engine v2.4.0, Copyright (c) 1998-2013 Zend Technologies
    
    sudo phpbrew ext install redis && sudo phpbrew ext enable redis
    sudo phpbrew ext install xdebug && sudo phpbrew ext enable xdebug
    
    php -m | grep xdebug && php -m | grep redis
        xdebug
        redis
    
    sudo pear config-set auto_discover 1
    sudo pear install pear.phpunit.de/PHPUnit
    
    which phpunit
        /Users/<username>/.phpbrew/php/php-5.4.17/bin/phpunit

###Troubleshooting when `phpbrew` didn\'t enable the extension
Open the `php.ini` of the specific PHP version (e.g. `/Users/<username>/.phpbrew/php/php-5.5.0/etc/php.ini`) and add the following lines manually to it.

    extension_dir = "/Users/<username>/.phpbrew/php/php-5.5.0/lib/php/extensions/no-debug-non-zts-20121212"
    
    extension=redis.so
    
    zend_extension="/Users/<username>/.phpbrew/php/php-5.5.0/lib/php/extensions/no-debug-non-zts-20121212/xdebug.so"
    
Check extension availability with `php -m`.

###Enable autocompletion for `zsh` 
This comes in quite handy when switching the PHP versions, without having to do a `phpbrew list` lookup to get the actual installed ones, as the installed ones will get suggested via autocompletion.

    cd /usr/local/share/zsh-completions
    curl -O https://raw.github.com/c9s/phpbrew/master/completion/zsh/_phpbrew
    source ~/.zshrc

###Drawback of `phpbrew switch <php-version>`
The activation of the apxs PHP module via the `LoadModule` statement has to be done manually in `/private/etc/apache2/httpd.conf`, and afterwards the Apache webserver needs to be restarted.

    LoadModule php5_module libexec/apache2/libphp5.5.0.so
    #LoadModule php5_module libexec/apache2/libphp5.4.17.so
    #LoadModule php5_module libexec/apache2/libphp5.3.27.so

###Removal of the former homebrew-php installation

Remove the PHP related brew formulars and taps™. 

    sudo brew uninstall php54 php54-intl php54-redis php54-xdebug

    brew untap homebrew/dupes
    brew untap josegonzalez/homebrew-php

Remove the possible PATH expansion from `~/.bash_profile` or `~/.zshrc`.

    export PATH="$(brew --prefix php54)"/bin:$PATH

Remove the `LoadModule` statement from `/private/etc/apache2/httpd.conf`.

    LoadModule php5_module /usr/local/Cellar/php54/5.4.16/libexec/apache2/libphp5.so';
    }
}

<?php

/**
Please DO NOT modify this file directly.
*/

namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class RequirementTopic extends GitHubTopic
{
    public $id = 'requirement';
    public $url = 'https://github.com/phpbrew/phpbrew/wiki/Requirement.md';
    public $title = 'Requirement';

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
        return 'The requirements are for you to run phpbrew and build your PHP. to build your PHP, you need a lot of libraries installed on your system.

The instructions below help you to get the things done.

### Platform support

* Mac OS 10.5+
* Ubuntu
* Debian

### Dependencies

* PHP5.3+
* curl
* gcc, binutil, autoconf, libxml, zlib, readline

### Mac OS X Requirement

MacPorts users:

```bash
port install curl automake autoconf icu depof:php5 depof:php5-gd mcrypt re2c gettext openssl
```

HomeBrew users:

```bash
brew install automake autoconf curl pcre re2c mhash libtool icu4c gettext jpeg libxml2 mcrypt gmp libevent
brew link icu4c
```

### Ubuntu 13.04 & 14.04 Requirement

**Please note that you need to disable suhosin patch to run phpbrew.**

#### Install Minimum Requirement (pure php without default variant)

```bash
apt-get build-dep php5
apt-get install -y php5 php5-dev php-pear autoconf automake curl build-essential libxslt1-dev re2c libxml2 libxml2-dev php5-cli bison libbz2-dev libreadline-dev
```

#### Install Medium Requirement (+gd +openssl +gettext +mhash +mcrypt +icu)

```bash
apt-get build-dep php5
apt-get install -y php5 php5-dev php-pear autoconf automake curl build-essential libxslt1-dev re2c libxml2 libxml2-dev php5-cli bison libbz2-dev libreadline-dev
apt-get install -y libfreetype6 libfreetype6-dev libpng12-0 libpng12-dev libjpeg-dev libjpeg8-dev libjpeg8  libgd-dev libgd3 libxpm4
apt-get install -y libssl-dev openssl
apt-get install -y gettext libgettextpo-dev libgettextpo0
apt-get install -y libicu48 libicu-dev
apt-get install -y libmhash-dev libmhash2
apt-get install -y libmcrypt-dev libmcrypt4
```

#### Database-Related Requirement

With MySQL:

```bash
apt-get install mysql-server mysql-client libmysqlclient-dev libmysqld-dev
```

With PostgreSQL:

```bash
apt-get install postgresql postgresql-client postgresql-contrib
```


### Fedora Requirement

```bash
yum install php php-devel php-pear bzip2-devel yum-utils bison re2c libmcrypt-devel libpqxx-devel libxslt-devel
yum-builddep php
```

### Cent OS Requirement

**Please note that you need to disable suhosin patch to run phpbrew.**

Cent OS requirement setup

```bash
sudo rpm -Uvh http://repo.webtatic.com/yum/centos/5/latest.rpm

# If you don\'t have php
sudo yum install --enablerepo=webtatic php php-xml
wget http://pkgs.repoforge.org/rpmforge-release/rpmforge-release-0.5.2-2.el5.rf.x86_64.rpm
sudo rpm -Uvh rpmforge-release-0.5.2-2.el5.rf.x86_64.rpm
sudo yum install --enablerepo=rpmforge re2c libmhash
```

Reference: http://matome.naver.jp/odai/2133887830324055901
';
    }
}

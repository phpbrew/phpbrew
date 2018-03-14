PHPBrew
=======

*本文档其它语言：[English](README.md), [Português - BR](README.pt-br.md), [日本語](README.ja.md), [中文](README.cn.md).*

[![Build Status](https://travis-ci.org/phpbrew/phpbrew.svg?branch=master)](https://travis-ci.org/phpbrew/phpbrew)
[![Coverage Status](https://img.shields.io/coveralls/phpbrew/phpbrew.svg)](https://coveralls.io/r/phpbrew/phpbrew)
[![StyleCI](https://styleci.io/repos/2468290/shield?style=flat)](https://styleci.io/repos/2468290)
[![Gitter](https://badges.gitter.im/phpbrew/phpbrew.svg)](https://gitter.im/phpbrew/phpbrew?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

phpbrew 是一个构建、安装多版本 PHP 到用户根目录的工具。

phpbrew 能做什么？

- 易于配置环境变量，无需担心路径问题。
- 支持使用不同参数编译 PHP，例如：PDO，Mysql，sqlite，debug 等等...
- 编译 Apache php 模块，且支持多版本。
- 易于将 PHP 安装到用户根目录，无需 root 权限。
- 易于切换版本，兼容 bash / zsh shell。
- 自动特性检测。
- 易于安装、启用 PHP 扩展到当前环境。
- 支持在系统全局环境安装多版本 PHP。
- 优化 HomeBrew 和 MacPorts 的路径检测。

<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/01.png"/>
<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/03.png"/>

## 安装需求

在开始之前，请先查看：[Requirement](https://github.com/phpbrew/phpbrew/wiki/Requirement)。
你需要先安装部分开发包用于编译 PHP。

## 安装

```bash
curl -L -O https://github.com/phpbrew/phpbrew/raw/master/phpbrew
chmod +x phpbrew

# Move phpbrew to somewhere can be found by your $PATH
sudo mv phpbrew /usr/local/bin/phpbrew
```

## 快速入门

急不可待？请直接查看：[Quick Start](https://github.com/phpbrew/phpbrew/wiki/Quick-Start)（英文）。

## 开始使用

接下来，假定你有充足的时间来学习，这将会是一个循序前进的教程来教会你如何配置 phpbrew。

### 初始设置

初始化当前环境：

```bash
phpbrew init
```

在 `.bashrc` 或 `.zshrc` 文件增加如下行：

```bash
[[ -e ~/.phpbrew/bashrc ]] && source ~/.phpbrew/bashrc
```

对于 Fish shell 用户，在 `~/.config/fish/config.fish` 文件增加如下行：

```fish
source ~/.phpbrew/phpbrew.fish
```

若你在使用系统全局环境的 phpbrew，你或许需要设置共享的 phpbrew 根目录，例如：

```bash
mkdir -p /opt/phpbrew
phpbrew init --root=/opt/phpbrew
```

### 库路径设置

你需要设置一个默认前缀用于查找库文件，可选值：`macports`，`homebrew`，`debian`，`ubuntu` 或是自定义路径。

对于 Homebrew 用户：

```bash
phpbrew lookup-prefix homebrew
```

对于 Macports 用户：

```bash
phpbrew lookup-prefix macports
```

## 基础用法

列出已知 PHP 版本：

```bash
phpbrew known

7.0: 7.0.3, 7.0.2, 7.0.1, 7.0.0 ...
5.6: 5.6.18, 5.6.17, 5.6.16, 5.6.15, 5.6.14, 5.6.13, 5.6.12, 5.6.11 ...
5.5: 5.5.32, 5.5.31, 5.5.30, 5.5.29, 5.5.28, 5.5.27, 5.5.26, 5.5.25 ...
5.4: 5.4.45, 5.4.44, 5.4.43, 5.4.42, 5.4.41, 5.4.40, 5.4.39, 5.4.38 ...
5.3: 5.3.29, 5.3.28 ...
```

也可以列出更多次要版本：

```bash
$ phpbrew known --more
```

更新已发布信息：

```bash
$ phpbrew update
```

获取旧版本（低于5.4）：

> 请注意：我们不保证能够正确编译 PHP 官方停止维护的版本，请不要提交关于编译旧版本的 Issus，此类 Issue 将不会修复。

```bash
$ phpbrew update --old
```

列出已知的旧版本（低于5.4）：

```bash
$ phpbrew known --old
```

## 编译属于你的 PHP

使用默认参数编译安装 PHP 非常简单：

```bash
$ phpbrew install 5.4.0 +default
```

这里我们推荐使用已包含绝大多数公共参数的 `default`（默认）参数集合。如果你需要「最小安装」，删掉`default`执行即可。

你可以使用`-j`或`--jobs`选项启用并行编译，例如：

```bash
$ phpbrew install -j $(nproc) 5.4.0 +default
```

包含测试：

```bash
$ phpbrew install --test 5.4.0
```

包含调试信息：

```bash
$ phpbrew -d install --test 5.4.0
```

安装老版本（低于5.3）：

```bash
$ phpbrew install --old 5.2.13
```

安装给定主版本的最新补丁包：

```bash
$ phpbrew install 5.6
```

安装预发布版本：

```bash
$ phpbrew install 7.2.0alpha1
$ phpbrew install 7.2.0beta2
$ phpbrew install 7.2.0RC3
```

通过指定 GitHub tag 或 branch 安装：

```bash
$ phpbrew install github:php/php-src@PHP-7.2 as php-7.2.0-dev
```

安装下一个（非稳定）版本：

```bash
$ phpbrew install next as php-7.3.0-dev
```

## 清除编译目录

```bash
$ phpbrew clean php-5.4.0
```

## 参数

PHPBrew 已经为你整理好配置选项，你只需简单地指定某个参数名即可，phpbrew 会自动在配置过程中检测引用目录、编译选项。

PHPBrew 提供了一些默认参数，以及一些虚拟参数。「默认参数」已经引用绝大多数公共参数；「虚拟参数」引用多个实际的参数，你可以使用一个虚拟参数一次性启用多个参数。

只需要执行`variants`子命令，即可列出这些参数：

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

在参数前添加`+`前缀，代表启用此参数，例如：

    +mysql

在参数前添加`-`前缀，代表禁用此参数，例如：

    -mysql

举个例子。假设你需要使用默认参数，并启用数据库支持（mysql，sqlite，postgresql）编译 PHP，只需执行：

```bash
$ phpbrew install 5.4.5 +default+dbs
```

也可以：

```bash
$ phpbrew install 5.3.10 +mysql+sqlite+cgi

$ phpbrew install 5.3.10 +mysql+debug+pgsql +apxs2

$ phpbrew install 5.3.10 +pdo +mysql +pgsql +apxs2=/usr/bin/apxs2
```

使用 pgsql (PostgreSQL) 扩展编译 PHP：

```bash
$ phpbrew install 5.4.1 +pgsql+pdo
```

若你的 Mac 上已经安装 postgresql，也可以使用特定目录编译 pgsql 扩展：

```bash
$ phpbrew install 5.4.1 +pdo+pgsql=/opt/local/lib/postgresql91/bin
```

pgsql 的路径即为`pg_config`所在目录，你可以在`/opt/local/lib/postgresql91/bin`找到它。

另外，你可以使用`neutral`参数来纯净编译 PHP：

```bash
$ phpbrew install 5.4.1 +neutral
```

`neutral`意味着 phpbrew 不会增加包括`--disable-all`在内的任何额外编译参数，但部分用于支持安装`pear`的参数（例如`--enable-libxml`）依旧会被添加。

更多细节，请移步 [PHPBrew Cookbook](https://github.com/phpbrew/phpbrew/wiki)（英文）。

## 更多配置选项

你可以执行如下命令，支持更多拓展配置：

```bash
$ phpbrew install 5.3.10 +mysql +sqlite -- \
    --enable-ftp --apxs2=/opt/local/apache2/bin/apxs
```

## 切换版本

临时切换 PHP 版本：

```bash
$ phpbrew use 5.4.22
```

切换默认 PHP 版本：

```bash
$ phpbrew switch 5.4.18
```

停止服务：

```bash
$ phpbrew off
```

若需要启用 Apache PHP 模块，请注释或移除以下设置项：

```bash
$ sudo vim /etc/httpd/conf/httpd.conf
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.21.so
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.20.so
```

## 列出已安装的 PHP

```bash
$ phpbrew list
```

## 扩展安装器

请查看：[Extension Installer](https://github.com/phpbrew/phpbrew/wiki/Extension-Installer)（英文）。

## 配置 php.ini

配置当前 PHP 版本的 php.ini 文件，只需执行如下命令即可：

```bash
$ phpbrew config
```

若需要切换编辑器，可执行如下命令指定 EDITOR 环境变量：

```bash
export EDITOR=vim
phpbrew config
```

## 升级 phpbrew

只需执行 `self-update` 即可从 GitHub 的 `master` 分支安装 phpbrew 最新版本。

```bash
$ phpbrew self-update
```

## 已安装的 PHP

你可以在 `~/.phpbrew/php` 目录找到已安装的 PHP。例如，5.4.20 版本位于：

    ~/.phpbrew/php/5.4.20/bin/php

你可以手动修改其 php.ini：

    ~/.phpbrew/php/5.4.20/etc/php.ini

而 PHP 扩展的配置文件位于：

Extension configuration files should be put in:

    ~/.phpbrew/php/5.4.20/var/db
    ~/.phpbrew/php/5.4.20/var/db/xdebug.ini
    ~/.phpbrew/php/5.4.20/var/db/apc.ini
    ~/.phpbrew/php/5.4.20/var/db/memcache.ini
    等等...

## 一键切换目录

切换至 PHP 编译目录：

```bash
$ phpbrew build-dir
```

切换至 PHP dist 目录：

```bash
$ phpbrew dist-dir
```

切换至 PHP etc 目录：

```bash
$ phpbrew etc-dir
```

切换至 PHP var 目录：

```bash
$ phpbrew var-dir
```

## PHP FPM

phpbrew 同样提供一些有用的命令用于管理 php-fpm。使用它们之前，请确认在编译时启用了 `+fpm` 参数。

启动 php-fpm：

```bash
$ phpbrew fpm start
```

停止 php-fpm：

```bash
$ phpbrew fpm stop
```

列出 php-fpm 模块：

```bash
phpbrew fpm module
```

测试 php-fpm 配置：

```bash
phpbrew fpm test
```

编辑 php-fpm 配置：

```bash
phpbrew fpm config
```

> 已安装的 `php-fpm` 位于 `~/.phpbrew/php/php-*/sbin` 目录。
>
> 对应的 `php-fpm.conf` 文件位于 `~/.phpbrew/php/php-*/etc/php-fpm.conf.default` 目录。
>
> 你可以把默认配置复制到自定义路径再使用，例如：
>
>     cp -v ~/.phpbrew/php/php-*/etc/php-fpm.conf.default
>         ~/.phpbrew/php/php-*/etc/php-fpm.conf
>
>     php-fpm --php-ini {php config file} --fpm-config {fpm config file}

## 安装拓展应用

phpbrew 提供部分常用应用命令。

### 安装 Composer

```bash
$ phpbrew app get composer
```

### 安装 PHPUnit

```bash
phpbrew app get phpunit
```

## 启用版本信息 Prompt

使用`"PHPBREW_SET_PROMPT=1"`变量可将 PHP 版本信息加入 Shell Prompt。

此变量默认值为`"PHPBREW_SET_PROMPT=0"`（即禁用），将如下行加入`~/.bashrc`文件，确保其在`source ~/.phpbrew/bashrc`之前，即可启用此功能：

```bash
export PHPBREW_SET_PROMPT=1
```

使用`.phpbrew/bashrc`内定义的`phpbrew_current_php_version`函数，可将版本信息嵌入到 Shell Prompt。你可以将版本信息设置到 `PS1` 变量内，例如：

```bash
PS1=" \$(phpbrew_current_php_version) \$ "
```


已知问题
------------

- 对于 PHP-5.3+ 版本，"Building intl 64-bit fails on OS X" <https://bugs.php.net/bug.php?id=48795>

- 将 GD 扩展编译进 PHP，你需要指定 libpng 目录、libjpeg 目录，例如：

    $ phpbrew install php-5.4.10 +default +mysql +intl +gettext +apxs2=/usr/bin/apxs2 \
        -- --with-libdir=lib/x86_64-linux-gnu \
           --with-gd=shared \
           --enable-gd-natf \
           --with-jpeg-dir=/usr \
           --with-png-dir=/usr


故障排查
-------------------

请移步：[TroubleShooting](https://github.com/phpbrew/phpbrew/wiki/TroubleShooting)（英文）。


常见问答
-------------------------

Q: 如何使用不同的参数编译相同 PHP 版本？

A: 截至目前，你可以安装 php-5.x.x 并重命名其目录 /Users/phpbrew/.phpbrew/php/php-5.x.x（例如：php-5.x.x-super），并安装另一个 php5.x.x。


参与贡献
------------------
请移步：[Contribution](https://github.com/phpbrew/phpbrew/wiki/Contribution)（英文）。


文档
-------------
请移步：[Wiki](https://github.com/phpbrew/phpbrew/wiki)（英文）。


作者
------

- Yo-An Lin (c9s)  <yoanlin93 _at_ gmail.com>
- Márcio Almad <marcio3w _at_ gmail.com>


授权
--------

请查看：[LICENSE](LICENSE) 文件。


[t-link]: https://travis-ci.org/phpbrew/phpbrew "Travis Build"
[s-link]: https://scrutinizer-ci.com/g/phpbrew/phpbrew/?branch=master "Code Quality"
[p-link]: https://packagist.org/packages/marc/phpbrew "Packagist"
[sl-link]: https://insight.sensiolabs.com/projects/02d1fd01-8a70-4fe4-a550-381a3c0e33f3 "Sensiolabs Insight"
[c-badge]: https://coveralls.io/repos/phpbrew/phpbrew/badge.png?branch=master
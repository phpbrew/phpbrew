PHPBrew
==========

*他の言語でもお読み頂けます: [English](README.md), [日本語](README.ja.md).*

[![Build Status](https://travis-ci.org/phpbrew/phpbrew.svg?branch=master)](https://travis-ci.org/phpbrew/phpbrew)
[![Coverage Status](https://img.shields.io/coveralls/phpbrew/phpbrew.svg)](https://coveralls.io/r/phpbrew/phpbrew)

phpbrewは異なるバージョンのPHPを`$HOME`ディレクトリにビルドしてインストールすることができます。

phpbrewは環境変数の管理もします。なので、望む時にいつでもPHPのバージョンを切り替えたり、一時的にバージョンを変更して使ったりすることが可能になっています。

phpbrewがあなたのためにやってくれること:

- configureスクリプトのオプションはバリアント(variant)へとシンプル化されているので、パスに悩まされることはもうありません！
- PDO、mysql、sqlite、debugなど様々なバリアント(variant)を持つPHPをビルドしてくれます。
- apacheのPHPモジュールをコンパイルし、異なるバージョンごとに分けて管理してくれます。
- PHPをビルドしてホームディレクトリにインストールするので、root権限が必要ありません。
- バージョン間の切り替えがとても簡単でbash/zshといったシェルに統合されています。
- 自動的に機能を検知します。
- PHP拡張モジュールを現在の環境にインストールして有効化することが簡単にできます。
- システムワイドな環境へ複数のPHPをインストールすることができます。
- HomeBrewとMacPorts向けにパスの検知が最適化されています。

<img width="600" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/01.png"/>

## 必要要件

始める前に[Requirement](https://github.com/phpbrew/phpbrew/wiki/Requirement)をご覧ください。
PHPをビルドするための開発用パッケージをインストールする必要があります。

## phpbrewのインストール

phpbrewをダウンロードするだけです：

```bash
curl -L -O https://github.com/phpbrew/phpbrew/raw/master/phpbrew
chmod +x phpbrew
sudo mv phpbrew /usr/bin/phpbrew
```

## 基本的な使い方

まず、お使いのシェル向けにbash scriptを初期化しておきます。

```bash
$ phpbrew init
```

そして、次の行を`.bashrc` または `.zshrc` ファイルに追加します：

```bash
$ source ~/.phpbrew/bashrc
```

phpbrewがライブラリを探す際の、お好みのデフォルトプレフィックスを準備しておくこともできます。
オプションは、`macports`, `homebrew`, `debian`, `ubuntu` またはカスタムのパスが使えます。

Homebrewユーザーの場合:

```bash
$ phpbrew lookup-prefix homebrew
```

Macportsユーザーの場合:

```bash
$ phpbrew lookup-prefix macports
```

既知のバージョンを一覧表示します：

```bash
$ phpbrew known
5.6:  5.6.1, 5.6.0 ...
5.5:  5.5.17, 5.5.16, 5.5.15, 5.5.14, 5.5.13, 5.5.12, 5.5.11, 5.5.10 ...
5.4:  5.4.33, 5.4.32, 5.4.31, 5.4.30, 5.4.29, 5.4.28, 5.4.27, 5.4.26 ...
5.3:  5.3.29, 5.3.28, 5.3.27, 5.3.26, 5.3.25, 5.3.24, 5.3.23, 5.3.22 ...
```

古いバージョン（5.3以前）を一覧表示します：

```bash
$ phpbrew known --old
```

PHP のリリース情報を更新します:

```bash
$ phpbrew known --update
```

## ビルド（build）とインストール

シンプルに`default`variant (variant については後述します)でPHPをビルドしてインストールします：

```bash
$ phpbrew install 5.4.0 +default
```

`default` variant set をお薦めします。`default` variant set は最もよく使われているバリアント群を含んでいます。
ミニマムインストールが必要であれば、`default` バリアントの指定を外してください。

`-j` または `--jobs` オプションを指定すると、並行ビルドを有効にすることができます。
以下に例を挙げます：

```bash
$ phpbrew install -j $(nproc) 5.4.0 +default
```

テストを実行する場合：

```bash
$ phpbrew install --test 5.4.0
```

debugメッセージを表示する場合：

```bash
$ phpbrew -d install --test 5.4.0
```

古いバージョン(5.3以下)をインストールする場合:

```bash
$ phpbrew install --old 5.2.13
```
## ビルドをクリーンする


```bash
$ phpbrew clean
```

## バリアント (variants)

PHPBrewは`configure`スクリプトのオプションをあなたの代わりに管理してくれます。
シンプルにバリアント名を指定してください。そうすると、PHPBrewがincludeパスとビルドオプションを検知してくれます。

PHPBrewはdefault variantsといくつかのvirtual variantsを提供します。
default variantsは最もよく使われているvariantsを含んでいます。
virtual variantsはvariant群を定義するもので、1つの virtual variant を使用するだけで、一度に複数の variants を有効化することが可能になります。

これらのvariantsに何が含まれているかを調べるには、`variants` サブコマンドを実行して一覧を表示します:

```bash
$ phpbrew variants
Variants:
  all, apxs2, bcmath, bz2, calendar, cgi, cli, ctype, dba, debug, dom, embed,
  exif, fileinfo, filter, fpm, ftp, gcov, gd, gettext, hash, iconv, icu,
  imap, intl, ipc, ipv6, json, kerberos, mbregex, mbstring, mcrypt, mhash,
  mysql, openssl, pcntl, pcre, pdo, pgsql, phar, posix, readline, session,
  soap, sockets, sqlite, tidy, tokenizer, xml_all, xmlrpc, zip, zlib, gmp


Virtual variants:
  dbs:      sqlite, mysql, pgsql, pdo
  mb:       mbstring, mbregex
  neutral:
  default:  filter, dom, bcmath, ctype, mhash, fileinfo, pdo, posix, ipc,
            pcntl, bz2, zip, cli, json, mbstring, mbregex, calendar, sockets, readline,
            xml_all

Using variants to build PHP:

  phpbrew install 5.3.10 +default
  phpbrew install 5.3.10 +mysql +pdo
  phpbrew install 5.3.10 +mysql +pdo +apxs2
  phpbrew install 5.3.10 +mysql +pdo +apxs2=/usr/bin/apxs2 
```

variantを有効化するには、`+`をvariant名の最初にプレフィックスとして付けます。
例えば、

    +mysql

variantを無効化するには、`-`をvariant名の最初にプレフィックスとして付けます。
例えば、

    -debug

以下に例を挙げます。defaultオプションとデータベースサポート(mysql, sqlite, postgresql)を有効にしてPHPをビルドしたい場合、以下を実行します:

```bash
$ phpbrew install 5.4.5 +default+dbs
```

さらに追加のオプションを指定してPHPをビルドすることもできます:

```bash
$ phpbrew install 5.3.10 +mysql+sqlite+cgi

$ phpbrew install 5.3.10 +mysql+debug+pgsql +apxs2

$ phpbrew install 5.3.10 +pdo +mysql +pgsql +apxs2=/usr/bin/apxs2
```

pgsql(Postgresql)拡張を有効にしてPHPをビルドするには:

```bash
$ phpbrew install 5.4.1 +pgsql+pdo
```

Mac OS Xでpostgresqlのbase dirを指定してpgsql拡張をビルドするには:

```bash
$ phpbrew install 5.4.1 +pdo+pgsql=/opt/local/lib/postgresql91/bin
```

pgsqlパス指定は`pg_config`の位置で、`pg_config`は /opt/local/lib/postgresql91/bin で見つけられるでしょう。


ニュートラル(中立的)なコンパイルオプションでPHPをビルドするには、`neutral` virtual variant を指定します。
`neutral` virtual variantは `--disable-all` も含めて余計なコンパイルオプションを極力追加しません。
しかし、`pear`のインストールをサポートするために、いくつかのオプション(例えば `--enable-libxml`)は自動的に追加されます。
`neutral` variantでPHPをビルドするには:

```bash
$ phpbrew install 5.4.1 +neutral
```


さらなる詳細は、[PHPBrew Cookbook](https://github.com/phpbrew/phpbrew/wiki)を参照してください。


## 追加オプション

configureスクリプトに追加の引数を渡すには、以下のようにしてください:

```bash
$ phpbrew install 5.3.10 +mysql +sqlite -- \
    --enable-ftp --apxs2=/opt/local/apache2/bin/apxs
```

## 使用(use)と切り替え(switch)

use (一時的なバージョンの切り替え):

```bash
$ phpbrew use 5.4.22
```

switch (デフォルトで使用するバージョンを切り替える):

```bash
$ phpbrew switch 5.4.18
```

phpbrewを使用するのをやめる:

```bash
$ phpbrew off
```

apacheモジュールを有効にしている場合、忘れずにその設定もコメントアウトするか削除するかしてください。

```bash
$ sudo vim /etc/httpd/conf/httpd.conf
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.21.so
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.20.so
```

## インストールされたPHPを一覧表示する

```bash
$ phpbrew list
```

##PHP拡張のインストーラー

PHP拡張を簡単にインストールすることもできます。PHPのソースコードと一緒に配布されているものやPECLで配布されているのどちらにも対応しています。

PHPのソースコード内にPHP拡張のディレクトリが見つかった場合、PHPBrewは自動的にPHPのソースディレクトリに移り、そのPHP拡張をインストールします。

PHPのソースコード内にPHP拡張が見つからなかった場合、PHPBrewはそのPHP拡張パッケージを PECL <http://pecl.php.net> から取得します。

また、PHPBrewはPHP拡張の設定を作成してインストールされたPHP拡張を有効化してくれます。なので、自分自身で設定ファイルを書いて有効化する必要はありません。
PHP拡張は次のディレクトリにあります:

    ~/.phpbrew/php/php-{version}/var/db


## 最も簡単なPHP拡張のインストール方法

安定度タグ(stability tag)を指定してインストールするには:

```bash
$ phpbrew ext install xdebug stable
$ phpbrew ext install xdebug latest
$ phpbrew ext install xdebug beta
```

バージョン名を指定してインストールするには:

```bash
$ phpbrew ext install xdebug 2.0.1
```

カスタムしたオプションを指定してインストールするには:

```bash
$ phpbrew ext install yaml -- --with-yaml=/opt/local
```

## 拡張モジュールを有効にします

PECL経由で拡張モジュールをインストールして、自分自身で有効化することも可能です:

```bash
pecl install mongo
phpbrew ext enable mongo
```

`ext enable`コマンドで`{currentPHPbase}/var/db/{extension name}.ini`に設定ファイルを作成して拡張を有効化することが可能です。

### 現在のPHPバージョン向けの php.ini を設定する

以下を実行します:

```bash
$ phpbrew config
```

お気に入りのエディタを`EDITOR`環境変数に指定しておくこともできます:

```bash
export EDITOR=vim
phpbrew config
```


## PHPBrewのアップグレード

PHPBrewをアップグレードする場合、 `self-update` コマンドを実行するだけで済みます。
このコマンドでgithub上の `master` ブランチの最新バージョンをインストールすることができます：

```bash
$ phpbrew self-update
```


## インストールされたPHPファイル

インストールされたPHPファイルは`~/.phpbrew/php`に置かれます。例えば、php 5.4.20の場合は:

    ~/.phpbrew/php/5.4.20/bin/php

設定ファイルは以下の位置に置く必要があります:

    ~/.phpbrew/php/5.4.20/etc/php.ini

拡張モジュールの設定ファイルは以下の位置に置く必要があります:

    ~/.phpbrew/php/5.4.20/var/db
    ~/.phpbrew/php/5.4.20/var/db/xdebug.ini
    ~/.phpbrew/php/5.4.20/var/db/apc.ini
    ~/.phpbrew/php/5.4.20/var/db/memcache.ini
    ... etc

## ディレクトリを切り替えるお手軽なコマンド

PHPのbuildディレクトリに移動します。

```bash
$ phpbrew build-dir
```

PHPのdistディレクトリに移動します。

```bash
$ phpbrew dist-dir
```

PHPのetcディレクトリに移動します。

```bash
$ phpbrew etc-dir
```

PHPのvarディレクトリに移動します。

```bash
$ phpbrew var-dir
```

##PHPFPM

phpbrewはfpmを管理するための便利なサブコマンドも提供しています。
それらを使うには、phpをビルドする際に`+fpm`を有効化しておくことを覚えておいてください。

php-fpmを起動します:

```bash
$ phpbrew fpm start
```

php-fpmを停止します:

```bash
$ phpbrew fpm stop
```

php-fpmモジュールを表示します:

```bash
phpbrew fpm module
```

php-fpmの設定をテストします:

```bash
phpbrew fpm test
```

php-fpmの設定を編集します:

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

## 追加コンポーネントのインストール

### composerのインストール

```bash
$ phpbrew install-composer
```

### phpunitのインストール

```bash
phpbrew install-phpunit
```

## プロンプトにバージョン情報を表示する

PHPのバージョン情報をシェルプロンプトに追加するには、`"PHPBREW_SET_PROMPT=1"`変数を使うことができます。

デフォルト値は `"PHPBREW_SET_PROMPT=0"` (無効)です。有効化するには、`~/.bashrc`ファイルで `~/.phpbrew/bashrc`をsourceする前に以下の行を追加します。

```bash
export PHPBREW_SET_PROMPT=1
```

プロンプトにバージョン情報を埋め込むには、`phpbrew_current_php_version`シェル関数が使えて、これは`.phpbrew/bashrc`で定義されていて、`PS1`変数にバージョン情報を設定することができます。

例えば、

```bash
PS1=" \$(phpbrew_current_php_version) \$ "
```


既知の問題点：
--------------

- PHP-5.3以上のバージョンで、"intl 64ビットのビルドがOS Xで失敗する" <https://bugs.php.net/bug.php?id=48795>

- GD拡張を指定してPHPをビルドするには、`libpng dir`と`libjpeg dir`を指定する必要があります。例えば、

    $ phpbrew install php-5.4.10 +default +mysql +intl +gettext +apxs2=/usr/bin/apxs2 \
        -- --with-libdir=lib/x86_64-linux-gnu \
           --with-gd=shared \
           --enable-gd-natf \
           --with-jpeg-dir=/usr \
           --with-png-dir=/usr

トラブルシューティング
-------------------

[TroubleShooting](https://github.com/phpbrew/phpbrew/wiki/TroubleShooting)をご覧ください。


FAQ
-------------------------

Q: 異なるコンパイルオプションを指定した、同一バージョンのPHPはどうすれば実現可能ですか？

A: 今のところ、php5.x.xをインストールして`/Users/phpbrew/.phpbrew/php/php-5.x.x`フォルダを新しい名前にリネームすることで実現可能です。例えば、php-5.x.x-superにリネームして別の php-5.3.3をインストールする、といったように。


コントリビュート
------------------

[Contribution](https://github.com/phpbrew/phpbrew/wiki/Contribution)をご覧ください。


ドキュメンテーション
-------------

[Wiki](https://github.com/phpbrew/phpbrew/wiki)をご覧ください。


Author
------

- Yo-An Lin (c9s)  <cornelius.howl _at_ gmail.com>


ライセンス
--------

[LICENSE](LICENSE)をご覧ください。



[t-link]: https://travis-ci.org/phpbrew/phpbrew "Travis Build"
[s-link]: https://scrutinizer-ci.com/g/phpbrew/phpbrew/?branch=master "Code Quality"
[p-link]: https://packagist.org/packages/marc/phpbrew "Packagist"
[sl-link]: https://insight.sensiolabs.com/projects/02d1fd01-8a70-4fe4-a550-381a3c0e33f3 "Sensiolabs Insight"
[c-badge]: https://coveralls.io/repos/phpbrew/phpbrew/badge.png?branch=master

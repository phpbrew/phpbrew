PHPBrew
==========

[![Build Status](https://travis-ci.org/phpbrew/phpbrew.svg?branch=master)](https://travis-ci.org/phpbrew/phpbrew)
[![Coverage Status](https://img.shields.io/coveralls/phpbrew/phpbrew.svg)](https://coveralls.io/r/phpbrew/phpbrew)

phpbrwe は異なるバージョンの PHP を $HOME ディレクトリにビルドしてインストールすることができます。

phpbrew は環境変数の管理もします。なので、望む時にいつでも PHP のバージョンを切り替えたり、一時的にバージョンを変更して使ったりすることが可能になっています。

phpbrew があなたのためにやってくれること:

- configure スクリプトのオプションは variants へとシンプル化されているので、パスに悩まされることはもうありません！
- PDO、mysql、sqlite、debugなど様々なバリアント（variant）を持つPHPをビルドしてくれます。
- apache の PHP モジュールをコンパイルし、異なるバージョンごとに分けて管理してくれます。
- PHP をビルドしてホームディレクトリにインストールするので、root 権限が必要ありません。
- バージョン間の切り替えがとても簡単で bash/zsh といったシェルに統合されています。
- 自動的に機能を検知します。
- PHP 拡張モジュールを現在の環境にインストールして有効化することが簡単にできます。
- システムワイドな環境へ複数の PHP をインストールすることができます。
- HomeBrew と MacPorts 向けにパスの検知が最適化されています。

<img width="600" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/01.png"/>

## 必要要件
始める前に [Requirement](https://github.com/phpbrew/phpbrew/wiki/Requirement) をご覧ください。
PHP をビルドするための開発用パッケージをインストールする必要があります。

## phpbrew のインストール

phpbrew をダウンロードするだけです：

```bash
curl -L -O https://github.com/phpbrew/phpbrew/raw/master/phpbrew
chmod +x phpbrew
sudo mv phpbrew /usr/bin/phpbrew
```

## 基本的な使い方

まず、お使いのシェル向けに bash script を初期化しておきます。

```bash
$ phpbrew init
```

そして、次の行を`.bashrc` または `.zshrc` ファイルに追加します：

```bash
$ source ~/.phpbrew/bashrc
```

phpbrew がライブラリを探す際の、お好みのデフォルトプレフィックスを準備しておくこともできます。
オプションは、`macports`, `homebrew`, `debian`, `ubuntu` またはカスタムのパスが使えます。

Homebrew ユーザーの場合:

```bash
$ phpbrew lookup-prefix homebrew
```

Macports ユーザーの場合:

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
シンプルに `default` variant (variant については後述します)で PHP をビルドしてインストールします：

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

古いバージョン(5.3 以下)をインストールする場合:

```bash
$ phpbrew install --old 5.2.13
```
## ビルドをクリーンする


```bash
$ phpbrew clean
```

## バリアント（variants）

PHPBrewは `configure` スクリプトのオプションをあなたの代わりに管理してくれます。
シンプルにバリアント名を指定してください。そうすると、PHPBrew が include パスとビルドオプションを検知してくれます。

PHPBrew は default variants といくつかの virtual variants を提供します。
default variants は最もよく使われている variants を含んでいます。
virtual variants は variant 群を定義するもので、1つの virtual variant を使用するだけで、一度に複数の variants を有効化することが可能になります。

これらの variants に何が含まれているかを調べるには、`variants` サブコマンドを実行して一覧を表示します:

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

To enable one variant, simply add a prefix `+` before the variant name, eg
ある variant を有効化するには、`+` を variant 名の最初にプレフィックスとして付けます。
例えば、

    +mysql

ある variant を無効化するには、`-` を variant 名の最初にプレフィックスとして付けます。
例えば、

    -debug

以下に例を挙げます。default オプションとデータベースサポート(mysql, sqlite, postgresql)を有効にして PHP をビルドしたい場合、以下を実行します:

```bash
$ phpbrew install 5.4.5 +default+dbs
```

さらに追加のオプションを指定して PHP をビルドすることもできます:

```bash
$ phpbrew install 5.3.10 +mysql+sqlite+cgi

$ phpbrew install 5.3.10 +mysql+debug+pgsql +apxs2

$ phpbrew install 5.3.10 +pdo +mysql +pgsql +apxs2=/usr/bin/apxs2
```

pgsql (Postgresql) 拡張を有効にして PHP をビルドするには:

```bash
$ phpbrew install 5.4.1 +pgsql+pdo
```

Mac OS X で postgresql の base dir を指定して pgsql 拡張をビルドするには:

```bash
$ phpbrew install 5.4.1 +pdo+pgsql=/opt/local/lib/postgresql91/bin
```

pgsql パス指定は `pg_config` の位置で、`pg_config` は /opt/local/lib/postgresql91/bin で見つけられるでしょう。


ニュートラル(中立的)なコンパイルオプションで PHP をビルドするには、`neutral` virtual variant を指定します。
`neutral` virtual variant は `--disable-all` も含めて余計なコンパイルオプションを極力追加しません。
しかし、`pear` のインストールをサポートするために、いくつかのオプション(例えば `--enable-libxml`)は自動的に追加されます。
`neutral` variant で PHP をビルドするには:

```bash
$ phpbrew install 5.4.1 +neutral
```


さらなる詳細は、[PHPBrew Cookbook](https://github.com/phpbrew/phpbrew/wiki) を参照してください。


## 追加オプション

configure スクリプトに追加の引数を渡すには、以下のようにしてください:

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

phpbrew を使用するのをやめる:

```bash
$ phpbrew off
```

## インストールされたPHPを一覧表示する

```bash
$ phpbrew list
```

## PHP 拡張のインストーラー

PHP 拡張を簡単にインストールすることもできます。PHP のソースコードと一緒に配布されているものや PECL で配布されているのどちらにも対応しています。

PHP のソースコード内に PHP 拡張のディレクトリが見つかった場合、PHPBrew は自動的に PHP のソースディレクトリに移り、その PHP 拡張をインストールします。

PHP のソースコード内に PHP 拡張が見つからなかった場合、PHPBrew はその PHP 拡張パッケージを PECL <http://pecl.php.net> から取得します。

また、PHPBrew は PHP 拡張の設定を作成してインストールされた PHP 拡張を有効化してくれます。なので、自分自身で設定ファイルを書いて有効化する必要はありません。
PHP 拡張は次のディレクトリにあります:

    ~/.phpbrew/php/php-{version}/var/db


## 最も簡単な PHP 拡張のインストール方法

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

PECL 経由で拡張モジュールをインストールして、自分自身で有効化することも可能です:

```bash
pecl install mongo
phpbrew ext enable mongo
```

`ext enable` コマンドで {current php base}/var/db/{extension name}.ini に設定ファイルを作成して拡張を有効化することが可能です。

### 現在の PHP バージョン向けの php.ini を設定する

以下を実行します:

```bash
$ phpbrew config
```

お気に入りのエディタを EDITOR 環境変数に指定しておくこともできます:

```bash
export EDITOR=vim
phpbrew config
```


## PHPBrewのアップグレード

PHPBrewをアップグレードする場合、 `self-update` コマンドを実行するだけで済みます。
このコマンドで github 上の `master` ブランチの最新バージョンをインストールすることができます：

```bash
$ phpbrew self-update
```


## インストールされたPHPファイル

インストールされた PHP ファイルは `~/.phpbrew/php` に置かれます。例えば、php 5.4.20 の場合は:

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

PHP の build ディレクトリに移動します。

```bash
$ phpbrew build-dir
```

PHP の dist ディレクトリに移動します。

```bash
$ phpbrew dist-dir
```

PHP の etc ディレクトリに移動します。

```bash
$ phpbrew etc-dir
```

PHP の var ディレクトリに移動します。

```bash
$ phpbrew var-dir
```

## PHP FPM

phpbrew は fpm を管理するための便利なサブコマンドも提供しています。
それらを使うには、php をビルドする際に `+fpm` を有効化しておくことを覚えておいてください。

php-fpm を起動します:

```bash
$ phpbrew fpm start
```

php-fpm を停止します:

```bash
$ phpbrew fpm stop
```

php-fpm モジュールを表示します:

```bash
phpbrew fpm module
```

php-fpm の設定をテストします:

```bash
phpbrew fpm test
```

php-fpm の設定を編集します:

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

### composer のインストール

```bash
$ phpbrew install-composer
```

### phpunit のインストール

```bash
phpbrew install-phpunit
```

## プロンプトにバージョン情報を表示する

PHP のバージョン情報をシェルプロンプトに追加するには、`"PHPBREW_SET_PROMPT=1"` 変数を使うことができます。

デフォルト値は `"PHPBREW_SET_PROMPT=0"` (無効) です。有効化するには、`~/.bashrc`
ファイルで `~/.phpbrew/bashrc` を source する前に以下の行を追加します。

```bash
export PHPBREW_SET_PROMPT=1
```

プロンプトにバージョン情報を埋め込むには、 `phpbrew_current_php_version` シェル関数が使えて、
これは `.phpbrew/bashrc` で定義されていて、 `PS1` 変数にバージョン情報を設定することができます。
例えば、

```bash
PS1=" \$(phpbrew_current_php_version) \$ "
```


既知の問題点：
--------------

- PHP-5.3以上のバージョンで、"intl 64ビットのビルドが OS X で失敗する" <https://bugs.php.net/bug.php?id=48795>

- GD 拡張を指定して PHP をビルドするには、libpng dir と libjpeg dir を指定する必要があります。例えば、

    $ phpbrew install php-5.4.10 +default +mysql +intl +gettext +apxs2=/usr/bin/apxs2 \
        -- --with-libdir=lib/x86_64-linux-gnu \
           --with-gd=shared \
           --enable-gd-natf \
           --with-jpeg-dir=/usr \
           --with-png-dir=/usr

トラブルシューティング
-------------------
[TroubleShooting](https://github.com/phpbrew/phpbrew/wiki/TroubleShooting) をご覧ください。


FAQ
-------------------------

Q: How do I have the same version with different compile option?
Q: 異なるコンパイルオプションを指定した、同一バージョンの PHP はどうすれば実現可能ですか？

A: Currently, you can install php5.x.x and rename the /Users/phpbrew/.phpbrew/php/php-5.x.x folder to the new name, for example, php-5.x.x-super , and install another php-5.3.3
A: 今のところ、php5.x.x をインストールして /Users/phpbrew/.phpbrew/php/php-5.x.x フォルダを新しい名前にリネームすることで実現可能です。
   例えば、php-5.x.x-super にリネームして別の php-5.3.3 をインストールする、といったように。


コントリビュート
------------------
[Contribution](https://github.com/phpbrew/phpbrew/wiki/Contribution) をご覧ください。


ドキュメンテーション
-------------
[Wiki](https://github.com/phpbrew/phpbrew/wiki) をご覧ください。


Author
------

- Yo-An Lin (c9s)  <cornelius.howl _at_ gmail.com>


ライセンス
--------

[LICENSE](LICENSE) をご覧ください。



[t-link]: https://travis-ci.org/phpbrew/phpbrew "Travis Build"
[s-link]: https://scrutinizer-ci.com/g/phpbrew/phpbrew/?branch=master "Code Quality"
[p-link]: https://packagist.org/packages/marc/phpbrew "Packagist"
[sl-link]: https://insight.sensiolabs.com/projects/02d1fd01-8a70-4fe4-a550-381a3c0e33f3 "Sensiolabs Insight"
[c-badge]: https://coveralls.io/repos/phpbrew/phpbrew/badge.png?branch=master

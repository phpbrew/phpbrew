PHPBrew
==========

*他の言語でもお読み頂けます:  [English](README.md), [Português - BR](README.pt-br.md), [日本語](README.ja.md).*

[![Build Status](https://travis-ci.org/phpbrew/phpbrew.svg?branch=master)](https://travis-ci.org/phpbrew/phpbrew)
[![Coverage Status](https://img.shields.io/coveralls/phpbrew/phpbrew.svg)](https://coveralls.io/r/phpbrew/phpbrew)
[![Gitter](https://badges.gitter.im/phpbrew/phpbrew.svg)](https://gitter.im/phpbrew/phpbrew?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

phpbrewは異なるバージョンのPHPを`$HOME`ディレクトリにビルドしてインストールすることができます。

phpbrewがあなたのためにやってくれること:

- configureスクリプトのオプションはバリアント(variant)へとシンプル化されているので、パスに悩まされることはもうありません！
- PDO、mysql、sqlite、debugなど様々なバリアント(variant)を持つPHPをビルドしてくれます。
- apacheのPHPモジュールをコンパイルし、異なるバージョンごとに分けて管理してくれます。
- PHPをビルドしてホームディレクトリにインストールするので、root権限が必要ありません。
- bash/zshといったシェルに統合されていて、バージョン間の切り替えがとても簡単です。
- 自動的に機能を検知します。
- PHP拡張モジュールを現在の環境にインストールして有効化することが簡単にできます。
- システムワイドな環境に複数のPHPをインストールすることができます。
- HomeBrewとMacPorts向けにパスの検知が最適化されています。

<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/01.png"/>
<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/03.png"/>

## 必要要件

始める前に[Requirement](https://github.com/phpbrew/phpbrew/wiki/Requirement)をご覧ください。
PHPをビルドするための開発用パッケージをインストールする必要があります。

## クイックスタート

あなたがせっかちな人なら [Quick Start](https://github.com/phpbrew/phpbrew/wiki/Quick-Start) をご覧ください :-p

## GETTING STARTED

OK、あなたに取り組むための十分な時間があるみたいですので、始めるのに役に立つチュートリアルを順に見ていきましょう。

### インストール

```bash
curl -L -O https://github.com/phpbrew/phpbrew/releases/latest/download/phpbrew.phar
chmod +x phpbrew.phar

# $PATH の通っているディレクトリにファイルを移動します
sudo mv phpbrew.phar /usr/local/bin/phpbrew
```

シェルで bash スクリプトを初期化します:

```bash
phpbrew init
```

`.bashrc` や `.zshrc` に以下の行を追加します:

```bash
[[ -e ~/.phpbrew/bashrc ]] && source ~/.phpbrew/bashrc
```

fish シェルを使っている人たちは、`~/.config/fish/config.fish` に以下の行を追加します:

```fish
source ~/.phpbrew/phpbrew.fish
```

システムワイドに phpbrew を使うつもりなら、例えば以下のように、共有の phpbrew ルートディレクトリを準備します:

```bash
mkdir -p /opt/phpbrew
phpbrew init --root=/opt/phpbrew
```

### ライブラリ探索用のプレフィックスをセットアップする

ライブラリを探索するためのデフォルトプレフィックスを好きなようにセットアップすることができます。利用可能なオプションは `macports`, `homebrew`, `debian`, `ubuntu`, カスタムパスです。

Homebrew を使っている場合:

```bash
phpbrew lookup-prefix homebrew
```

Macports を使っている場合:

```bash
phpbrew lookup-prefix macports
```

## 基本的な使い方

PHP のバージョンを一覧する:

```bash
phpbrew known

7.0: 7.0.3, 7.0.2, 7.0.1, 7.0.0 ...
5.6: 5.6.18, 5.6.17, 5.6.16, 5.6.15, 5.6.14, 5.6.13, 5.6.12, 5.6.11 ...
5.5: 5.5.32, 5.5.31, 5.5.30, 5.5.29, 5.5.28, 5.5.27, 5.5.26, 5.5.25 ...
5.4: 5.4.45, 5.4.44, 5.4.43, 5.4.42, 5.4.41, 5.4.40, 5.4.39, 5.4.38 ...
5.3: 5.3.29, 5.3.28 ...
```

マイナーバージョンまで表示するには:

```bash
$ phpbrew known --more
```

リリース情報を更新するには:

```bash
$ phpbrew update
```

5.4 未満の古いバージョンを取得するには:

> 公式でサポートされていない PHP のバージョンのビルドが成功することを phpbrew
> は保証しないことを覚えておいてください。また、古いバージョンに関する issue を
> レポートしてこないようにしてください。その issue は修正されません。

```bash
$ phpbrew update --old
```

5.4 未満の古いバージョンを一覧する:

```bash
$ phpbrew known --old
```

## 自分用の PHP のビルドを始める

シンプルに default バリアントで PHP をビルドしてインストールするには:

```bash
$ phpbrew install 5.4.0 +default
```

`default` バリアントセットをおすすめします。なぜならこのバリアントセットは
最もよく使われているバリアントを含んでいるからです。

もし最小構成でのインストールを求めているなら、`default` バリアントセットの指定を外してください。

`-j` or `--jobs` オプションを渡すことで並行ビルドを有効にできあます。例えば以下のように:

```bash
$ phpbrew install -j $(nproc) 5.4.0 +default
```

テストを実行する場合：

```bash
$ phpbrew install --test 5.4.0
```

debugメッセージを表示する場合:

```bash
$ phpbrew -d install --test 5.4.0
```

5.3 未満の古いバージョンをインストールするには:

```bash
$ phpbrew install --old 5.2.13
```

あるリリースバージョンの最新のパッチバージョンをインストールする場合:

```bash
$ phpbrew install 5.6
```

プレリリース状態のバージョンをインストールする場合:

```bash
$ phpbrew install 7.2.0alpha1
$ phpbrew install 7.2.0beta2
$ phpbrew install 7.2.0RC3
```

GitHub のタグやブランチ名でインストールする場合:

```bash
$ phpbrew install github:php/php-src@PHP-7.2 as php-7.2.0-dev
```

次の(不安定)バージョンをインストールする場合:

```bash
$ phpbrew install next as php-7.3.0-dev
```

## ビルドをクリーンする


```bash
$ phpbrew clean php-5.4.0
```

## バリアント (variants)

PHPBrew は `configure` スクリプトのオプションをあなたの代わりに管理してくれます。
シンプルにバリアント名を指定してください。そうすると、PHPBrew が include パスとビルドオプションを検知してくれます。

PHPBrew は `default` バリアントといくつかの「仮想バリアント」(Virtual variants)を提供します。
`default` バリアントは最もよく使われているバリアントを含んでいます。
仮想バリアントはいくつものバリアントのセットを定義するもので、ひとつの仮想バリアントを使用するだけで、一度に複数のバリアントを有効化します。

これらのバリアントに何が含まれているかを調べるには、`phpbrew variants` コマンドを実行して一覧を表示します。

バリアントを有効化するには、`+`をバリアント名の前に付けます。
例えば、

    +mysql

バリアントを無効化するには、`-`をバリアント名の前に付けます。
例えば、

    -debug

以下に例を挙げます。`default` オプションとデータベースサポート(mysql, sqlite, postgresql)を有効にしてPHPをビルドしたい場合、以下を実行します:

```bash
$ phpbrew install 5.4.5 +default+dbs
```

さらに追加のオプションを指定してPHPをビルドすることもできます:

```bash
$ phpbrew install 5.3.10 +mysql+sqlite+cgi

$ phpbrew install 5.3.10 +mysql+debug+pgsql +apxs2

$ phpbrew install 5.3.10 +pdo +mysql +pgsql +apxs2=/usr/bin/apxs2
```

pgsql(PostgreSQL)拡張を有効にしてPHPをビルドするには:

```bash
$ phpbrew install 5.4.1 +pgsql+pdo
```

Mac OS XでPostgreSQLのディレクトリを指定してpgsql拡張をビルドするには:

```bash
$ phpbrew install 5.4.1 +pdo+pgsql=/opt/local/lib/postgresql91/bin
```

pgsqlパス指定は`pg_config`の位置で、`pg_config`は /opt/local/lib/postgresql91/bin で見つけられるでしょう。


ニュートラル(中立的)なコンパイルオプションでPHPをビルドするには、`neutral` 仮想バリアントを指定します。
`neutral` 仮想バリアントは `--disable-all` も含めて余計なコンパイルオプションを極力追加しません。
しかし、`pear`のインストールをサポートするために、いくつかのオプション(例えば `--enable-libxml`)は自動的に追加されます。

`neutral` variantでPHPをビルドするには:

```bash
$ phpbrew install 5.4.1 +neutral
```


そのほかの詳細は[PHPBrew Cookbook](https://github.com/phpbrew/phpbrew/wiki)をご覧ください。


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

phpbrewを使用するのをやめる:

```bash
$ phpbrew off
```

apache モジュールを有効にしている場合、忘れずにその設定もコメントアウトするか削除するかしてください。

```bash
$ sudo vim /etc/httpd/conf/httpd.conf
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.21.so
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.20.so
```

## Extention Installer

[Extension Installer](https://github.com/phpbrew/phpbrew/wiki/Extension-Installer) をご覧ください。

### 現在利用中の PHP バージョンに合わせて php.ini を設定する

シンプルに以下を実行します:

```bash
$ phpbrew config
```

好きなエディタを EDITOR 環境変数に指定することもできます:

```bash
export EDITOR=vim
phpbrew config
```

## phpbrew をアップグレードする

phpbrew をアップグレードするには、シンプルに `self-update` コマンドを実行します。
このコマンドで、GitHub から `master` ブランチの最新バージョンをインストールできます。

```bash
$ phpbrew self-update
```

## インストール済みのPHPを一覧表示する

```bash
$ phpbrew list
```

インストール済みの PHP は `~/.phpbrew/php` に置かれます。例えば、PHP 5.4.20 は、

    ~/.phpbrew/php/5.4.20/bin/php

に置かれます。

設定ファイルは、

    ~/.phpbrew/php/5.4.20/etc/php.ini

に置くべきです。

extension 向けの設定ファイルは、

    ~/.phpbrew/php/5.4.20/var/db
    ~/.phpbrew/php/5.4.20/var/db/xdebug.ini
    ~/.phpbrew/php/5.4.20/var/db/apc.ini
    ~/.phpbrew/php/5.4.20/var/db/memcache.ini
    ... etc

に置くべきです。

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

## PHP FPM

phpbrew は fpm を管理するための便利なサブコマンドも提供しています。
それらを使うには、PHP をビルドする際に `+fpm` を有効化しておくことを覚えておいてください。

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

> インストールされた `php-fpm` は `~/.phpbrew/php/php-*/sbin` にあります。
>
> 対応する `php-fpm.conf` は `~/.phpbrew/php/php-*/etc/php-fpm.conf.default` にあります。
> デフォルトの設定ファイルをお好みの場所にコピーできます。例えば、
>
>     cp -v ~/.phpbrew/php/php-*/etc/php-fpm.conf.default
>         ~/.phpbrew/php/php-*/etc/php-fpm.conf
>
>     php-fpm --php-ini {php config file} --fpm-config {fpm config file}

## プロンプトにバージョン情報を表示する

PHP のバージョン情報をシェルプロンプトに追加するには、`"PHPBREW_SET_PROMPT=1"`変数を使うことができます。

デフォルト値は `"PHPBREW_SET_PROMPT=0"` (無効)です。有効化するには、`~/.bashrc`ファイルで `~/.phpbrew/bashrc`をsourceする前に以下の行を追加します。

```bash
export PHPBREW_SET_PROMPT=1
```

プロンプトにバージョン情報を埋め込むには、`phpbrew_current_php_version`シェル関数が利用可能です。
これは`.phpbrew/bashrc`で定義されていて、`PS1`変数にバージョン情報を設定することができます。

例えば、

```bash
PS1=" \$(phpbrew_current_php_version) \$ "
```


既知の問題点：
--------------

- PHP-5.3 以上のバージョンで、OS X で64bit 版 intl のビルドが失敗する <https://bugs.php.net/bug.php?id=48795>

- GD 拡張を指定して PHP をビルドするには、`libpng dir` と `libjpeg dir` を指定する必要があります。
  例えば、

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

Q: 同じバージョンで異なるコンパイルオプションを指定したPHPをインストールすることはできますか？

A: 今のところ、php5.x.x をインストールして `/Users/phpbrew/.phpbrew/php/php-5.x.x` フォルダを別の名前にリネームすることで実現可能です。例えば、php-5.x.x-super にリネームして新しく php-5.3.3 をインストールする、といったように。


貢献するには
------------------

[Contribution](https://github.com/phpbrew/phpbrew/wiki/Contribution) をご覧ください。


ドキュメンテーション
-------------

[Wiki](https://github.com/phpbrew/phpbrew/wiki) をご覧ください。


Author
------

- Yo-An Lin (c9s)  <cornelius.howl _at_ gmail.com>
- Márcio Almad <marcio3w _at_ gmail.com>


ライセンス
--------

[LICENSE](LICENSE)をご覧ください。



[t-link]: https://travis-ci.org/phpbrew/phpbrew "Travis Build"
[s-link]: https://scrutinizer-ci.com/g/phpbrew/phpbrew/?branch=master "Code Quality"
[p-link]: https://packagist.org/packages/marc/phpbrew "Packagist"
[sl-link]: https://insight.sensiolabs.com/projects/02d1fd01-8a70-4fe4-a550-381a3c0e33f3 "Sensiolabs Insight"
[c-badge]: https://coveralls.io/repos/phpbrew/phpbrew/badge.png?branch=master

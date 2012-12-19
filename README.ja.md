PHPBrew
==========

PHPBrewは異なるバージョンのPHPをホームディレクトリにビルド（build）とインストールすることができます。

環境変数の管理もでき、個人の需要に応じてバージョンを切り替えて利用することが可能です。

PHPBrewのできること：:

- PDO、mysql、sqlite、debugなど様々なバリアント（variant）を持つPHPをビルドします。
- apache phpモジュールをコンパイルし、ヴァージョンごとに分けます。
- 異なるバージョンのphpをホームディレクトリにインストールするので、ルートパーミッション（root permission）は必要なくなります。
- 簡易にバージョンを切り替えることができます。しかもbash/zshシェルと統合されました。
- 自動機能検知。
- system-wide環境に複数のPHPバージョンをインストールできます。

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/01.png"/>

<img width="600" src="https://raw.github.com/c9s/phpbrew/master/screenshots/03.png"/>


## 支援OS

* Mac OS 10.5+
* Ubuntu
* Debian

## 必須環境

* PHP5.3
* curl
* gcc, binutil, autoconf, libxml, zlib, readline

### Mac OS X 必須環境

MacPorts使用者：

```bash
port install curl automake autoconf icu $(port echo depof:php5)
```

HomeBrew使用者：

```bash
brew install automake autoconf curl pcre re2c mhash glibtool icu4c gettext jpeg libxml2 mcrypt gmp libevent
brew link icu4c
```

### Ubuntu/Debian必須環境

```bash
sudo apt-get install autoconf automake curl build-essential libxslt1-dev re2c libxml2-dev
sudo apt-get build-dep php5
```

### Cent OS必須環境

Cent OS必須環境の設定

```bash
sudo rpm -Uvh http://repo.webtatic.com/yum/centos/5/latest.rpm

# phpがない場合
sudo yum install --enablerepo=webtatic php php-xml
wget http://pkgs.repoforge.org/rpmforge-release/rpmforge-release-0.5.2-2.el5.rf.x86_64.rpm
sudo rpm -Uvh rpmforge-release-0.5.2-2.el5.rf.x86_64.rpm
sudo yum install --enablerepo=rpmforge re2c libmhash
```

参照: http://matome.naver.jp/odai/2133887830324055901

## インストールPHPBrew

PHPBrewをダウンロードしてください：

```bash
curl -O https://raw.github.com/c9s/phpbrew/master/phpbrew
chmod +x phpbrew
sudo cp phpbrew /usr/bin/phpbrew
```


## 基本用法

まずはシェル環境にbash scriptをInitします。

```bash
$ phpbrew init
```

そして次の行を`.bashrc` または`.zshrc` ファイルに追加します：

```bash
$ source ~/.phpbrew/bashrc
```

既知バージョンを一覧表示にします：

```bash
$ phpbrew known
Available stable versions:
    php-5.3.10
    php-5.3.9
    php-5.3.8
    php-5.3.7
```

既知のサブバージョンを一覧表示にします：

```bash
$ phpbrew known --svn
```

古いバージョン（5.3以前）を一覧表示にします：

```bash
$ phpbrew known --old
```

## ビルド（build）とインストール

簡易にデフォルトバリアントのPHPをビルドとインストールします：

```bash
$ phpbrew install php-5.4.0 +default
```

こちらでは`default` VARIANTセットをお薦めします。デフォルトセットは常用のVARIANTクラスを収録している。最小限インストールの場合、`default` VARIANTセットを削除してください。


テスト：

```bash
$ phpbrew install --test php-5.4.0
```

debugメッセージ：

```bash
$ phpbrew -d install --test php-5.4.0
```





## バリアント（variants）

PHPBrewはConfigureオプションを管理できます。VARIANTのクラス名を指定するだけで、PHPBrewはincludeパスとオプションを検知します。

PHPBrewは常用のVARIANTクラスを収録し、デフォルトのVARIANTセットを提供しています。
収録されているVARIANTクラスを確認するには、簡単にサブコマンド`variants`を実行して一覧表示を確認できます：

```bash
$ phpbrew variants

Variants:
    pear
    mysql
    debug
    sqlite
    pgsql
    cli
    apxs2
    cgi
    soap
    pcntl
    ... (etc)
```

最新のPHPBrewは2つのVARIANTセットを提供しています：

1. default (最も常用のバリアント, 例えば：filter bcmath ctype fileinfo pdo posix ipc pcntl bz2 cli intl fpm calendar sockets readline, zip)
2. dbs (sqlite, mysql, pgsql, pdo)

例：

    $ phpbrew install php-5.4.5 +default+dbs

それ以外のVARIANTクラスのPHPをビルドすることも可能です：

    $ phpbrew install php-5.3.10 +mysql+sqlite+cgi

    $ phpbrew install php-5.3.10 +mysql+debug+pgsql +apxs2

    $ phpbrew install php-5.3.10 +mysql +pgsql +apxs2=/usr/bin/apxs2

PDOはデフォルトで起動されています。

PHPとpgSQL (Postgresql)拡張モジュールをビルドします：

    $ phpbrew install php-5.4.1 +pgsql

あるいはpostgresqlのベースディレクトリを生成してpgSQL拡張モジュールをビルドします：

    $ phpbrew install php-5.4.1 +pgsql=/opt/local/lib/postgresql91


注意点：

> 1. apacheのphpモジュールをビルドする場合、
> モジュールディレクトリのパーミッションを変更してください。
> 例： `/opt/local/apache2/modules/`。
> パーミッションは書き込む可能です。PHPBrewはパーミッションを変更することができます。
> インストールした後、httpd.conf設定ファイルを確認し、PHPモジュールを切り替わってください。
>
> 2. 現時点PHPBrewはapxs2 (apache2)しか支援しません。
複数のPHPをインストールした環境の下で`apxs2` VARIANTを有効化したなら、apacheのconfファイルは以下のように示されています：

    # LoadModule php5_module        modules/libphp5.3.10.so
    # LoadModule php5_module        modules/libphp5.4.0.so
    # LoadModule php5_module        modules/libphp5.4.0RC8.so
    # LoadModule php5_module        modules/libphp5.4.0RC7.so
    # LoadModule php5_module        modules/libphp5.4.1RC2.so
    # LoadModule php5_module        modules/libphp5.4.1.so
    # LoadModule php5_module        modules/libphp5.4.2.so
    LoadModule php5_module          modules/libphp5.4.4.so

コメント化や非コメント化を利用して簡単にphp5
apacheモジュールを無効化／有効化にすることができます。
編集したあとは必ずapache http サーバーを再起動してください:)

## エクストラオプション

エクストラconfigure引数を渡すには、以下のようにしてください:

    $ phpbrew install php-5.3.10 +mysql +sqlite -- \
      --enable-ftp --apxs2=/opt/local/apache2/bin/apxs

## 使用と切り替え

使用 (switch version temporarily):

```bash
$ phpbrew use php-5.4.0RC7
```

切り替え (特定のバージョンをデフォルトとして切り替えます)

```bash
$ phpbrew switch php-5.4.0
```

終了：

```bash
$ phpbrew off
```

## インストールされたPHPを一覧表示にします

```bash
$ phpbrew list
```

## PHPから拡張モジュールをビルドとインストール

(インストール手順の後):

    phpbrew install-ext pdo
    phpbrew install-ext mcrypt --with-mcrypt=/opt/local

## 拡張モジュールを有効にします

    pecl install mongo
    phpbrew enable mongo

 `enable` コマンドはconfig {current php base}/var/db/{extension name}.iniを作成し、拡張モジュールを有効にします。

## PHPBrewのアップグレード

PHPBrewをアップグレードする場合、To upgrade phpbrew, `self-update` コマンドを実行するだけで済みます。
このコマンドは最新バージョンのgithubの`master` branchをインストールすることができます：

    $ phpbrew self-update

## インストールされたPHPファイル

インストールされたPHPファイルは`~/.phpbrew/php`に置きます。例え、php 5.4.0RC7の場合は:

    ~/.phpbrew/php/5.4.0RC7/bin/php

設定ファイルは以下記の位置に置く必要があります:

    ~/.phpbrew/php/5.4.0RC7/etc/php.ini

拡張モジュールの設定ファイルは下記の位置に置く必要があります:

    ~/.phpbrew/php/5.4.0RC7/var/db
    ~/.phpbrew/php/5.4.0RC7/var/db/xdebug.ini
    ~/.phpbrew/php/5.4.0RC7/var/db/apc.ini
    ~/.phpbrew/php/5.4.0RC7/var/db/memcache.ini
    ... etc

## system-wide環境のPHPBrewインストールします：

まずは、sudoをルートユーザにするまたはルートユーザでログインします：

    sudo -i

そしてPHPBrewのbashrcを初期化します：

    phpbrew init

PHPBrewのパスを指定のパスにエクスポート（export）してから、 
~/.phpbrew/init　を編集してください。

    export PHPBREW_ROOT=/opt/phpbrew

PHPBrew bashrcをソース（source）する。

    source ~/.phpbrew/bashrc

インストールsystem-wide PHP：

    phpbrew install php-5.4.5 +default +dbs

これでPHPファイルは /opt/phpbrew の下にインストールされました。
HPBrewがビルドしたPHPをユーザーに使用させるには、bashユーザーがphpbrew/bashrcを読み込む前に`PHPBREW_ROOT` 環境を/etc/bashrc または/etc/profile.d/phpbrewにエクスポートする必要があります。。

    export PHPBREW_ROOT=/opt/phpbrew
    source /opt/phpbrew/bashrc

システムの安定を保つため、`root`を使ってPHPをインストールしてください。

非ルートのユーザーは新しいPHPをインストールまたは切り替わることができません。 

ファイルは非ルートユーザーでインストールする場合、パーミッションを修正してください。

    chown -R root: /opt/phpbrew


## バージョン情報プロンプトの有効化

PHPバージョン情報をシェルプロンプトに追加するには、`"PHPBREW_SET_PROMPT=1"` 変数を利用してください。

デフォルトは`"PHPBREW_SET_PROMPT=0"` (無効化). 有効化にする場合、`~/.phpbrew/bashrc`をソースする前に、下記の行を`~/.bashrc` ファイルに追加してください。

```sh
    export PHPBREW_SET_PROMPT=1
```

バージョン情報をプロンプトに埋め込む場合、`current_php_version` シェル関数を利用してください。この関数は`.phpbrew/bashrc`の中に定義されています。
そしてバージョン情報を`PS1`変数に設定することができます。
例：

```sh
    PHP_VERSION=$(current_php_version)
    PS1=" $PHP_VERSION \$ "
```


既知の問題点：
--------------

- For PHP-5.3+ versions, "Building intl 64-bit fails on OS X" <https://bugs.php.net/bug.php?id=48795>


ハッキング（Hacking）：
-------
まずはOnionをインストールしてください：

    $ curl http://install.onionphp.org/ | sh

依存性（dependency）インストール:

    $ onion -d install

初期化：

    $ php bin/phpbrew init

既知のバージョンを一覧表示にします：

    $ php bin/phpbrew known

インストール:

    $ php bin/phpbrew -d install --no-test 5.4.0RC7

PHPBrewが実行している動作を表示します：

    $ unset -f phpbrew
    $ which phpbrew

pharファイルをリコンパイルします：

    $ bash scripts/compile

PHP配布先
--------------------

- http://snaps.php.net/
- http://tw2.php.net/releases/
- http://downloads.php.net/stas/

コミュニティ
---------

お気軽にirc.freenode.netにて#php-twの話題に参加してください。

協力者
------------

* yftzeng
* Gasol

開発者
------
Yo-An Lin (c9s)  <cornelius.howl@gmail.com>


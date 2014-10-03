<?php
/**
This is an auto-generated file,
Please DO NOT modify this file directly.
*/
namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class TroubleshootingTopic  extends GitHubTopic {


public $remoteUrl = 'https://github.com/phpbrew/phpbrew/wiki/Troubleshooting.md';
public $title = 'Troubleshooting';
public $id = 'troubleshooting';


    public function getRemoteUrl() {
    return $this->remoteUrl;
}

    public function getId() {
    return $this->id;
}

    public function getContent() {
return '### can not configure with +gd

Run with `+gd=shared,/usr` variant, which will be expanded to `--with-gd=shared,/usr`, e.g.,

    phpbrew install 5.4.22 +gd=shared,/usr

### On Ubuntu 14.04, configure: error: freetype.h not found:

Execute the following command:

    ln -s /usr/include/freetype2 /usr/include/freetype2/freetype

### configure: error: Please reinstall the iconv library:

There is a php build system bug on Linux: <https://bugs.php.net/bug.php?id=48451>

Please try to configure with `+iconv=shared` or `+iconv`

     phpbrew --debug install 5.4.22 +iconv=shared
  
or

     phpbrew --debug install 5.4.22 +iconv

### iconv: undefined reference to `libiconv_open\'

https://bugs.php.net/bug.php?id=42547

https://bugs.php.net/bug.php?id=52611



### install php5.3.24 and it reports "unrecognized option -export-dynamic\'"

Please also add `+intl` variant, e.g.,

    phpbrew install 5.3.12 +default +intl

### library or header file not found

* check if you installed the library (.a or .so)
* check if you installed the header files (.h files)
* print the configure options to see which directory is using,
  run phpbrew install with `-d` or `--debug` flag to see what\'s the configure options, e.g.,

        $ phpbrew -d install 5.4.22

* search for the location of the required header files:

        $ locate foobar.h

* search for the location of the required so files:

        $ locate foobar.so

* try `php-config` to see the configure options of the current running php, and compare the options with your debug message, then adjust your variant to match the options.

    * If you still can not resolve the problem, then please fire an issue with your phpbrew version, OS, configure options.

### pcre.h is not found when installing extension


Make sure that you\'ve installed pcre package, you can run `pkg-config` to find out the include path:

```
pkg-config --libs --cflags libpcre
```

Then, set the include directory in the CFLAGS environment variable to install extension:

```sh
CFLAGS="-I/opt/local/include" phpbrew ext install pecl_HTTP
```





';
}

}


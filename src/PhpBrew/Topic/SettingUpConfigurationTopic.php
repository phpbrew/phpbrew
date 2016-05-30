<?php
/**
Please DO NOT modify this file directly.
*/

namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class SettingUpConfigurationTopic  extends GitHubTopic
{
    public $id = 'setting-up-configuration';
    public $url = 'https://github.com/phpbrew/phpbrew/wiki/Setting-up-Configuration.md';
    public $title = 'Setting up Configuration';

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
        return 'With the yaml config file you can do the following things:

* Add custom virtual variants feature
* Add presets of extensions

# YAML configuration file

The yaml configuration file can be set by using the following command:

```sh
phpbrew init -c=config.yaml
```

or alternatively:

```sh
phpbrew init --config=config.yaml
```



```yaml
variants:
    dev:
        bcmath:
        bz2:
        calendar:
        cli:
        ctype:
        dom:
        fileinfo:
        filter:
        ipc:
        json:
        mbregex:
        mbstring:
        mhash:
        mcrypt:
        gd:
          - --with-libdir=lib/x86_64-linux-gnu
          - --with-gd=shared
          - --enable-gd-natf
          - --with-jpeg-dir=/usr
          - --with-png-dir=/usr
extensions:
    dev:
        xhprof: latest
        xdebug: stable
```

# Custom virtual variants feature

The virtual variants are defined in the config file (see _variants_ in the config example above) and can overwrite the default configuration of the features (see _gd_ in the config example above), these config is only used if you call the custom virtual variant.

# Presets of extensions

Similar to the virtual variants you can define virtual extensions (see _extenions_ in the config example above). You can set also the version of the module.

The can be called by using the following command: _phpbrew ext install +dev_';
    }
}

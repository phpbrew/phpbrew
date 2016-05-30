<?php
/**
Please DO NOT modify this file directly.
*/

namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class HomeTopic  extends GitHubTopic
{
    public $id = 'home';
    public $url = 'https://github.com/phpbrew/phpbrew/wiki/Home.md';
    public $title = 'Home';

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
        return '### PHPBrew Guide

#### For Users

[[Requirement]]

[[Cookbook]]

[[TroubleShooting]]

[[Setting up Configuration]]

#### For Developers

[[Contribution]]

[[Release Process]]


### See also

* [[PHPBrew JA 日語指引]]

* [[Migrating from homebrew-php to phpbrew|Migrating-from-homebrew-php-to-phpbrew]] written by Raphael Stolt 

* [VERSION MANAGEMENT WITH PHPBREW](https://codio.com/s/blog/2014/03/phpbrew/) - March 10, 2014 by Freddy May';
    }
}

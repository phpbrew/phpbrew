<?php
/**
This is an auto-generated file,
Please DO NOT modify this file directly.
*/
namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class ReleaseProcessTopic  extends GitHubTopic {


public $id = 'release-process';
public $url = 'https://github.com/phpbrew/phpbrew/wiki/Release-Process.md';
public $title = 'Release Process';


    public function getRemoteUrl() {
    return $this->remoteUrl;
}

    public function getId() {
    return $this->id;
}

    public function getContent() {
return 'Before you roll out a new release, please download PHPRelease <https://github.com/c9s/PHPRelease>

The release steps is defined in phprelease.ini file, PHPRelease will run unit tests, compile phar file, git tagging and pushing for you.

The usage is pretty simple, but be sure to make all unit tests pass:


To release a minor version: (which is for new feature release, develop branch is merged into master branch)

    $ phprelease --bump-minor

To release a patch version: (which is for bug fixes release, bug fixes are on master branch)

    $ phprelease # bump patch version.';
}

}


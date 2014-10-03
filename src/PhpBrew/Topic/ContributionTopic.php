<?php
/**
This is an auto-generated file,
Please DO NOT modify this file directly.
*/
namespace PhpBrew\Topic;

use CLIFramework\Topic\GitHubTopic;

class ContributionTopic  extends GitHubTopic {


public $remoteUrl = 'https://github.com/phpbrew/phpbrew/wiki/Contribution.md';
public $title = 'Contribution';
public $id = 'contribution';


    public function getRemoteUrl() {
    return $this->remoteUrl;
}

    public function getId() {
    return $this->id;
}

    public function getContent() {
return '### Before You Start Your Work

Please discuss your idea first on GitHub issues with us before sending your pull request for new features and design.

To develop new features, please checkout `develop` branch for development.

For bug fix, you may fix it directly on `master`.


#### Development Policy

- Don\'t add too many dependencies just for few features.
- You should keep the compiled phar file (phpbrew.phar) as small as possible.
- You should add an unit test for the newly added feature.
- PRs should pass the travis-ci tests, your PR can get merged sooner if your PR passed these tests.
- Please follow the current coding style in PHPBrew.
- Don\'t replace under-layer components just for replacing components. The replacement MUST be for new obvious features.


#### Pull Request Proposal

Please describe the details of your PR. For new features, please consult the PR template below:

```
## Purpose

Describe the purpose of this PR.

## Changes

Describe the changes of this PR.

## Effect

Is there any effect if we merge this PR.

## Tests

The unit tests of this PR.

## Usage

How to enable/use this feature

```


### Setup Your Development Environment

#### Installing the dependencies

    $ composer install --dev

#### Changing Shell Script

The main shell script is in `phpbrew.sh`, you may modify it then re-source it in your shell:

    source phpbrew.sh

If everything works fine, you may update it to the InitCommand class:

    scripts/update-init-script

#### Changing PHP Code

If you need to modify PHP source, you may run `bin/phpbrew` to test your code:

    php bin/phpbrew {command}

If everything works fine, you may re-compile the phpbrew phar file:

    scripts/compile

The compilation needs `onion` to do the job, please download onion from http://github.com/c9s/Onion


### PHP Release channels

- http://snaps.php.net/
- http://www.php.net/releases/
- http://downloads.php.net/stas/

### Build Status

master: [![Build Status](https://travis-ci.org/phpbrew/phpbrew.png?branch=master)](https://travis-ci.org/phpbrew/phpbrew)

develop: [![Build Status](https://travis-ci.org/phpbrew/phpbrew.png?branch=develop)](https://travis-ci.org/phpbrew/phpbrew)


';
}

}


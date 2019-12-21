<?php

namespace PHPBrew\Tests\Extension;

use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PHPBrew\Extension\ExtensionDownloader;
use PHPBrew\Extension\Provider\BitbucketProvider;
use PHPBrew\Extension\Provider\GithubProvider;
use PHPBrew\Extension\Provider\PeclProvider;
use PHPBrew\Testing\CommandTestCase;

class KnownCommandTest extends CommandTestCase
{

    public $usesVCR = true;

    public function testPeclPackage()
    {

        $logger = new Logger();
        $logger->setQuiet();

        $provider = new PeclProvider($logger, new OptionResult());
        $provider->setPackageName('xdebug');

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult());
        $versionList = $extensionDownloader->knownReleases($provider);

        $this->assertNotCount(0, $versionList);
    }

    public function testGithubPackage()
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped('Avoid bugging GitHub on Travis since the test is likely to fail because of a 403');
        }

        $logger = new Logger();
        $logger->setQuiet();

        $provider = new GithubProvider();
        $provider->setOwner('phalcon');
        $provider->setRepository('cphalcon');
        $provider->setPackageName('phalcon');
        if (getenv('github_token')) { //load token from travis-ci
            $provider->setAuth(getenv('github_token'));
        }

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult());
        $versionList = $extensionDownloader->knownReleases($provider);
        $this->assertNotCount(0, $versionList);
    }

    public function testBitbucketPackage()
    {

        $logger = new Logger();
        $logger->setQuiet();

        $provider = new BitbucketProvider();
        $provider->setOwner('osmanov');
        $provider->setRepository('pecl-event');
        $provider->setPackageName('event');

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult());
        $versionList = $extensionDownloader->knownReleases($provider);

        $this->assertNotCount(0, $versionList);
    }
}

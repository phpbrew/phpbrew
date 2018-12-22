<?php

namespace PhpBrew\Extension;

use VCR\VCR;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;
use PhpBrew\Extension\Provider\BitbucketProvider;
use PhpBrew\Extension\Provider\GithubProvider;
use PhpBrew\Extension\Provider\PeclProvider;
use PhpBrew\Testing\CommandTestCase;

class KnownCommandTest extends CommandTestCase {

    public function setUp() {
        parent::setUp();

        VCR::turnOn();
        VCR::insertCassette('KnownCommandTest');
    }

    public function tearDown() {
        parent::tearDown();

        VCR::eject();
        VCR::turnOff();
    }

    public function testPeclPackage() {

        $logger = new Logger;
        $logger->setQuiet();

        $provider = new PeclProvider($logger, new OptionResult());
        $provider->setPackageName('xdebug');

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult);
        $versionList = $extensionDownloader->knownReleases($provider);

        $this->assertNotCount(0, $versionList);

    }

    public function testGithubPackage()
    {
        $logger = new Logger;
        $logger->setQuiet();

        $provider = new GithubProvider();
        $provider->setOwner('phalcon');
        $provider->setRepository('cphalcon');
        $provider->setPackageName('phalcon');
        if(getenv('github_token')) { //load token from travis-ci
            $provider->setAuth(getenv('github_token'));
        }

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult);
        $versionList = $extensionDownloader->knownReleases($provider);
        $this->assertNotCount(0, $versionList);
    }

    public function testBitbucketPackage() {

        $logger = new Logger;
        $logger->setQuiet();

        $provider = new BitbucketProvider();
        $provider->setOwner('osmanov');
        $provider->setRepository('pecl-event');
        $provider->setPackageName('event');

        $extensionDownloader = new ExtensionDownloader($logger, new OptionResult);
        $versionList = $extensionDownloader->knownReleases($provider);

        $this->assertNotCount(0, $versionList);

    }

}


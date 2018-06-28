<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Config;

/**
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Config\Configuration
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->object = new Configuration();
    }

    // hasRepoToken()
    // getRepoToken()

    /**
     * @test
     */
    public function shouldNotHaveRepoTokenOnConstruction()
    {
        $this->assertFalse($this->object->hasRepoToken());
        $this->assertNull($this->object->getRepoToken());
    }

    // hasServiceName()
    // getServiceName()

    /**
     * @test
     */
    public function shouldNotHaveServiceNameOnConstruction()
    {
        $this->assertFalse($this->object->hasServiceName());
        $this->assertNull($this->object->getServiceName());
    }

    // getCloverXmlPaths()

    /**
     * @test
     */
    public function shouldHaveEmptyCloverXmlPathsOnConstruction()
    {
        $this->assertEmpty($this->object->getCloverXmlPaths());
    }

    // getRootDir()

    /**
     * @test
     */
    public function shouldNotRootDirOnConstruction()
    {
        $this->assertNull($this->object->getRootDir());
    }

    // getJsonPath()

    /**
     * @test
     */
    public function shouldNotHaveJsonPathOnConstruction()
    {
        $this->assertNull($this->object->getJsonPath());
    }

    // isDryRun()

    /**
     * @test
     */
    public function shouldBeDryRunOnConstruction()
    {
        $this->assertTrue($this->object->isDryRun());
    }

    // isExcludeNoStatements()

    /**
     * @test
     */
    public function shouldNotBeExcludeNotStatementsOnConstruction()
    {
        $this->assertFalse($this->object->isExcludeNoStatements());
    }

    // isVerbose

    /**
     * @test
     */
    public function shouldNotBeVerboseOnConstruction()
    {
        $this->assertFalse($this->object->isVerbose());
    }

    // getEnv()

    /**
     * @test
     */
    public function shouldBeProdEnvOnConstruction()
    {
        $this->assertSame('prod', $this->object->getEnv());
    }

    // isTestEnv()

    /**
     * @test
     */
    public function shouldBeTestEnv()
    {
        $expected = 'test';

        $this->object->setEnv($expected);

        $this->assertSame($expected, $this->object->getEnv());
        $this->assertTrue($this->object->isTestEnv());
        $this->assertFalse($this->object->isDevEnv());
        $this->assertFalse($this->object->isProdEnv());
    }

    // isDevEnv()

    /**
     * @test
     */
    public function shouldBeDevEnv()
    {
        $expected = 'dev';

        $this->object->setEnv($expected);

        $this->assertSame($expected, $this->object->getEnv());
        $this->assertFalse($this->object->isTestEnv());
        $this->assertTrue($this->object->isDevEnv());
        $this->assertFalse($this->object->isProdEnv());
    }

    // isProdEnv()

    /**
     * @test
     */
    public function shouldBeProdEnv()
    {
        $expected = 'prod';

        $this->object->setEnv($expected);

        $this->assertSame($expected, $this->object->getEnv());
        $this->assertFalse($this->object->isTestEnv());
        $this->assertFalse($this->object->isDevEnv());
        $this->assertTrue($this->object->isProdEnv());
    }

    // setRootDir()

    /**
     * @test
     */
    public function shouldSetRootDir()
    {
        $expected = '/root';

        $same = $this->object->setRootDir($expected);

        $this->assertSame($same, $this->object);
        $this->assertSame($expected, $this->object->getRootDir());
    }
    // setRepoToken()

    /**
     * @test
     */
    public function shouldSetRepoToken()
    {
        $expected = 'token';

        $same = $this->object->setRepoToken($expected);

        $this->assertSame($same, $this->object);
        $this->assertSame($expected, $this->object->getRepoToken());
    }

    // setServiceName()

    /**
     * @test
     */
    public function shouldSetServiceName()
    {
        $expected = 'travis-ci';

        $same = $this->object->setServiceName($expected);

        $this->assertSame($same, $this->object);
        $this->assertSame($expected, $this->object->getServiceName());
    }

    // setCloverXmlPaths()

    /**
     * @test
     */
    public function shouldSetCloverXmlPaths()
    {
        $expected = array('/path/to/clover.xml');

        $same = $this->object->setCloverXmlPaths($expected);

        $this->assertSame($same, $this->object);
        $this->assertSame($expected, $this->object->getCloverXmlPaths());
    }

    // addCloverXmlPath()

    /**
     * @test
     */
    public function shouldAddCloverXmlPath()
    {
        $expected = '/path/to/clover.xml';

        $same = $this->object->addCloverXmlPath($expected);

        $this->assertSame($same, $this->object);
        $this->assertSame(array($expected), $this->object->getCloverXmlPaths());
    }

    // setJsonPath()

    /**
     * @test
     */
    public function shouldSetJsonPath()
    {
        $expected = '/path/to/coveralls-upload.json';

        $same = $this->object->setJsonPath($expected);

        $this->assertSame($same, $this->object);
        $this->assertSame($expected, $this->object->getJsonPath());
    }

    // setDryRun()

    /**
     * @test
     */
    public function shouldSetDryRunFalse()
    {
        $expected = false;

        $same = $this->object->setDryRun($expected);

        $this->assertSame($same, $this->object);
        $this->assertFalse($this->object->isDryRun());
    }

    /**
     * @test
     */
    public function shouldSetDryRunTrue()
    {
        $expected = true;

        $same = $this->object->setDryRun($expected);

        $this->assertSame($same, $this->object);
        $this->assertTrue($this->object->isDryRun());
    }

    // setExcludeNoStatements()

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsFalse()
    {
        $expected = false;

        $same = $this->object->setExcludeNoStatements($expected);

        $this->assertSame($same, $this->object);
        $this->assertFalse($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrue()
    {
        $expected = true;

        $same = $this->object->setExcludeNoStatements($expected);

        $this->assertSame($same, $this->object);
        $this->assertTrue($this->object->isExcludeNoStatements());
    }

    // setExcludeNoStatementsUnlessFalse()

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsFalseUnlessFalse()
    {
        $expected = false;

        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        $this->assertSame($same, $this->object);
        $this->assertFalse($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrueUnlessFalse()
    {
        $expected = true;

        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        $this->assertSame($same, $this->object);
        $this->assertTrue($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrueIfFalsePassedAndIfTrueWasSet()
    {
        $expected = false;

        $same = $this->object->setExcludeNoStatements(true);
        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        $this->assertSame($same, $this->object);
        $this->assertTrue($this->object->isExcludeNoStatements());
    }

    /**
     * @test
     */
    public function shouldSetExcludeNoStatementsTrueIfTruePassedAndIfTrueWasSet()
    {
        $expected = true;

        $same = $this->object->setExcludeNoStatements(true);
        $same = $this->object->setExcludeNoStatementsUnlessFalse($expected);

        $this->assertSame($same, $this->object);
        $this->assertTrue($this->object->isExcludeNoStatements());
    }

    // setVerbose()

    /**
     * @test
     */
    public function shouldSetVerboseFalse()
    {
        $expected = false;

        $same = $this->object->setVerbose($expected);

        $this->assertSame($same, $this->object);
        $this->assertFalse($this->object->isVerbose());
    }

    /**
     * @test
     */
    public function shouldSetVerboseTrue()
    {
        $expected = true;

        $same = $this->object->setVerbose($expected);

        $this->assertSame($same, $this->object);
        $this->assertTrue($this->object->isVerbose());
    }

    // setEnv()

    /**
     * @test
     */
    public function shouldSetEnv()
    {
        $expected = 'myenv';

        $same = $this->object->setEnv($expected);

        $this->assertSame($same, $this->object);
        $this->assertSame($expected, $this->object->getEnv());
    }
}

<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Entity\Exception;

/**
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Entity\Exception\RequirementsNotSatisfiedException
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class RequirementsNotSatisfiedExceptionTest extends \PHPUnit_Framework_TestCase
{
    // getReadEnv()

    /**
     * @test
     */
    public function shouldNotHaveReadEnvOnConstruction()
    {
        $object = new RequirementsNotSatisfiedException();

        $this->assertNull($object->getReadEnv());
    }

    // setReadEnv()

    /**
     * @test
     */
    public function shouldSetReadEnv()
    {
        $expected = array(
            'ENV_NAME' => 'value',
        );

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $this->assertSame($expected, $object->getReadEnv());
    }

    // getHelpMessage()

    /**
     * @test
     */
    public function shouldGetHelpMessageWithStringEnvVar()
    {
        $expected = array(
            'ENV_NAME' => 'value',
        );

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $message = $object->getHelpMessage();

        $this->assertContains("  - ENV_NAME='value'", $message);
    }

    // getHelpMessage()

    /**
     * @test
     */
    public function shouldGetHelpMessageWithSecretStringEnvVarHidden()
    {
        // Make sure the secret repo token is HIDDEN.
        $env = array(
            'COVERALLS_REPO_TOKEN' => 'secret',
        );
        $expected = "  - COVERALLS_REPO_TOKEN='********(HIDDEN)'";

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($env);

        $message = $object->getHelpMessage();

        $this->assertContains($expected, $message);
    }

    /**
     * @test
     */
    public function shouldGetHelpMessageWithSecretEmptyStringEnvVarShown()
    {
        // Make sure the secret repo token is shown when it's empty.
        $env = array(
            'COVERALLS_REPO_TOKEN' => '',
        );
        $expected = "  - COVERALLS_REPO_TOKEN=''";

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($env);

        $message = $object->getHelpMessage();

        $this->assertContains($expected, $message);
    }

    /**
     * @test
     */
    public function shouldGetHelpMessageWithIntegerEnvVar()
    {
        $expected = array(
            'ENV_NAME' => 123,
        );

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $message = $object->getHelpMessage();

        $this->assertContains('  - ENV_NAME=123', $message);
    }

    /**
     * @test
     */
    public function shouldGetHelpMessageWithBooleanEnvVar()
    {
        $expected = array(
            'ENV_NAME' => true,
        );

        $object = new RequirementsNotSatisfiedException();
        $object->setReadEnv($expected);

        $message = $object->getHelpMessage();

        $this->assertContains('  - ENV_NAME=true', $message);
    }
}

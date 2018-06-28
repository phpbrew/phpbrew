<?php
use CodeGen\Generator\ArrayAccessGenerator;
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\UserClass;

class ArrayAccessGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $generator = new ArrayAccessGenerator;
        $userClass = new UserClass('MyZoo');
        $userClass->addPublicProperty('animals', array(
            'tiger' => 'John',
            'cat'   => 'Lisa',
        ));
        $generator->generate('animals', $userClass);

        $userClass->requireAt('tests/generated/my_zoo.fixture');

        $zoo = new MyZoo;

        $this->assertTrue(isset($zoo['tiger']));
        $this->assertFalse(isset($zoo['other']));

        $this->assertEquals('John', $zoo['tiger']);
        $this->assertEquals('Lisa', $zoo['cat']);
    }
}


<?php
use CodeGen\UserClass;
use CodeGen\Testing\CodeGenTestCase;

class UserClassTest extends CodeGenTestCase
{
    public function testUserClassImplement()
    {
        $cls = new UserClass('impl');
        $cls->implementClass('iface');
        $this->assertCodeEqualsFile('tests/data/user_class_implement.fixture', $cls);
    }

    public function testUserClassAddMethod()
    {
        $cls = new UserClass('FooClass');
        $cls->addMethod('public', 'run', array('$a', '$b'), 'return $a + $b;');
        $this->assertCodeEqualsFile('tests/data/user_class_method.fixture', $cls);
    }

    public function testGeneratePsr4ClassUnder()
    {
        $class = new UserClass('Bar\Foo2Class');
        $path = $class->generatePsr4ClassUnder(array(
            'Bar\\' => 'tests/',
        ));
        $this->assertEquals('tests/Foo2Class.php',$path);
    }
}

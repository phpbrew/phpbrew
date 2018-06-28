<?php
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\UseClass;


class UseClassTest extends CodeGenTestCase
{
    public function testUseClass()
    {
        $cls = new UseClass('TestNamspace\testClass');

        $this->assertCodeEqualsFile('tests/data/use_class.fixture', $cls);
    }

    public function testUseClassAlias()
    {
        $cls = new UseClass('TestNamspace\testClass','testAlias');
        $this->assertCodeEqualsFile('tests/data/use_class_alias.fixture', $cls);
    }
}

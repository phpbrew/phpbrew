<?php
use CodeGen\Expr\ObjectProperty;
use CodeGen\Testing\CodeGenTestCase;

class ObjectPropertyTest extends CodeGenTestCase
{
    public function testObjectProperty()
    {
        $expr = new ObjectProperty('$obj', 'property');
        $this->assertCodeEqualsFile('tests/data/object_property.fixture', $expr);
    }
}

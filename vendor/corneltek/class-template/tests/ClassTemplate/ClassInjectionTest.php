<?php

class ClassInjectionTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        // create test class file
        file_put_contents('tests/tmp_class',<<<'CODE'
<?php
class InjectFoo {
    public $value = 2;
    public function __toString() {
        return $this->getValue();
    }
}
CODE
);
        require 'tests/tmp_class';
        $foo = new InjectFoo;
        ok($foo);

        $inject = new ClassTemplate\ClassInjection($foo);
        ok($inject);

        $inject->read();


        // so that we have getValue method now.
        $inject->appendContent('
            function getValue() {
                return $this->value;
            }
        ');

        $inject->write();

        // file_put_contents('tests/data/injected', $inject);
        is( file_get_contents('tests/data/injected'), $inject->__toString() );

        $inject->read();
        is( file_get_contents('tests/data/injected'), $inject->__toString() );

        $inject->write();
        is( file_get_contents('tests/data/injected'), $inject->__toString() );


        $inject->replaceContent('');
        $inject->write();

        // file_put_contents('tests/data/replaced',$inject);
        is( file_get_contents('tests/data/replaced'), $inject->__toString() );


        // TODO test the content
        unlink('tests/tmp_class');
    }
}


<?php
namespace Foo {
    trait TraitA { 
        public function hello() {
            return 'hello from A';
        }
    }

    trait TraitB { 
        public function hello() {
            return 'hello from B';
        }
    }
}

namespace {
use ClassTemplate\ClassFile;
use CodeGen\Testing\CodeGenTestCase;
use CodeGen\UseClass;

class ClassDeclareTraitTest extends CodeGenTestCase
{
    public function testTraitUseInsteadOf() {
        $classTemplate = new ClassTemplate\ClassFile('Foo\\TraitTest',array(
            'template' => 'Class.php.twig',
            'template_dirs' => array('src/ClassTemplate/Templates'),
        ));
        $classTemplate->useTrait('TraitA', 'TraitB')
            ->useInsteadOf('TraitA::hello', 'TraitB');
        $this->evalTemplate($classTemplate);
    }

    public function evalTemplate(ClassFile $classTemplate)
    {
        $code = $classTemplate->render();
        $tmpname = tempnam('/tmp', preg_replace('/\W/', '_', $classTemplate->class->getFullName()));
        file_put_contents($tmpname, $code);
        require $tmpname;
    }

    public function testTraitUseAs() {
        $classTemplate = new ClassTemplate\ClassFile('Foo\\TraitUseAsTest',array(
            'template' => 'Class.php.twig',
            'template_dirs' => array('src/ClassTemplate/Templates'),
        ));
        $classTemplate->useTrait('TraitA', 'TraitB')
            ->useInsteadOf('TraitB::hello', 'TraitA')
            ->useAs('TraitA::hello', 'talk');
        $this->evalTemplate($classTemplate);

        $foo = new Foo\TraitUseAsTest;
        ok($foo);
        is('hello from A',$foo->talk());
        is('hello from B',$foo->hello());
    }
}

}

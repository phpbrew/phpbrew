ClassTemplate
============================

ClassTemplate library is used for generating static PHP class files from a programmable interface with PHP.

SYNOPSIS
-------------

```php
$class1 = new ClassTemplate\ClassFile('Foo\\Bar22',array(
    'template' => 'Class.php.twig',
    'template_dirs' => array('src/ClassTemplate/Templates'),
));
ok($class1);

$class1->addConst('TYPE_A', 1);
$class1->addConst('TYPE_B', 2);
$class1->addConst('TYPE_C', 3);

$class1->addMethod('public','getTwo',[],'return 2;');
$class1->addMethod('public','getFoo',['$i'],'return $i;');

$class1->extendClass('FooClass');

$class1->extendClass('SplArray', true);

$code = $class1->render();
```

The above code outpus:

```php

<?php
namespace Foo;
class Bar22 {
    public function getTwo() {
        return 2;
    }

    public function getFoo($i) {
        return $i;
    }
}
```


INSTALL
------------------


Define the dependency in your composer.json file:

```json
{
    "require": {
        "corneltek/class-template": "*"
    }
}
```

Then run `composer.phar` to install:

```sh
$ composer install --dev
```


Classes
----------------------

### ClassProperty

```php
$property = new ClassProperty('key','123');
echo $property->render(); // public $key = 123;
```

### ClassMethod

```php
$method = new ClassMethod('methodName',[ '$i' ], 'return $i;');
```






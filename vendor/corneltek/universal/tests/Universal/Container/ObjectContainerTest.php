<?php
/*
 * This file is part of the {{ }} package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

class FooObjectBuilder
{
    public $i = 1;

    public function __invoke()
    {
        return 'foo' . $this->i++;
    }
}

use Universal\Container\ObjectContainer;

class ObjectContainerTest extends PHPUnit_Framework_TestCase 
{
    public function testSingletonBuilder() 
    {
        $container = new ObjectContainer;
        $container->std = function() { 
            return new \stdClass;
        };
        ok( $container->std );
        is( $container->std , $container->std );
    }

    public function testFactoryBuilder()
    {
        $container = new ObjectContainer;
        $container->registerFactory('std',function($args) { 
            return $args;
        });
        $a = $container->getObject('std',array(1));
        ok($a);

        $b = $container->getObject('std',array(2));
        ok($b);

        is(1,$a);
        is(2,$b);
    }

    public function testCallableObject()
    {
        $container = new ObjectContainer;
        $container->registerFactory('foo', new FooObjectBuilder);
        $foo = $container->getObject('foo');
        is('foo1',$foo);
        is('foo2',$container->foo);
        is('foo3',$container->foo);
    }
}


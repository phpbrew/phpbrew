<?php
namespace CodeGen\Frameworks\PHPUnit;

use CodeGen\ClassMethod;
use CodeGen\UserClass;
use Doctrine\Common\Inflector\Inflector;

class PHPUnitFrameworkTestCase extends UserClass
{
    public function __construct($title)
    {
        $class = Inflector::classify(preg_replace('/\W+/', ' ', $title));
        parent::__construct($class);
        $this->extendClass('PHPUnit_Framework_TestCase', true);
    }

    public function addTest($testName)
    {
        $methodName = 'test' . Inflector::classify($testName);
        $testMethod = new ClassMethod($methodName, array(), array());
        $testMethod->setScope('public');
        $this->methods[] = $testMethod;
        return $testMethod;
    }
}





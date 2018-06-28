<?php
namespace CodeGen\Generator;

use CodeGen\UserClass;
use ReflectionObject;
use ReflectionProperty;

/**
 * Generate UserClass for applciation based on the runtime object.
 */
class AppClassGenerator
{
    protected $options = array();

    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(
            'namespace' => null,
            'prefix' => 'App',
            'reflection_property_filter' => ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED,
            'property_filter' => null,
        ), $options);
    }


    protected function isValueExportable($value)
    {
        return is_array($value) || is_scalar($value) || is_null($value);
    }


    public function generate($object, UserClass $userClass = null)
    {
        $reflObject = new ReflectionObject($object);

        if (!$userClass) {
            $className = $this->options['prefix'] . $reflObject->getShortName();
            if (!$this->options['namespace'] && $reflObject->inNamespace()) {
                $namespace = $reflObject->getNamespaceName();
                $className = '\\' . $namespace . '\\' . $className;
            } else {
                $className = $this->options['namespace'] . '\\' . $className;
            }
            $userClass = new UserClass($className);
            $userClass->extendClass('\\' . $reflObject->getName(), false);
        }

        $properties = $reflObject->getProperties($this->options['reflection_property_filter']);
        $propertyFilter = $this->options['property_filter'];
        foreach ($properties as $reflProperty) {
            $reflProperty->setAccessible(true);

            if ($propertyFilter && !$propertyFilter($reflProperty)) {
                continue;
            }

            $propertyName = $reflProperty->getName();
            $propertyValue = $reflProperty->getValue($object);


            // check if the property value is exportable
            // $propertyValue
            if (!$this->isValueExportable($propertyValue)) {
                continue;
            }

            if ($reflProperty->isPublic()) {
                $userClass->addPublicProperty($propertyName, $propertyValue);
            } else if ($reflProperty->isProtected()) {
                $userClass->addProtectedProperty($propertyName, $propertyValue);
            }
            // $appClass->addPublicProperty('files', $this->files);
        }
        return $userClass;
    }
}





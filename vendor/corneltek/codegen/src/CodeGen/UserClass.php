<?php
namespace CodeGen;

use Exception;

class UserClass implements Renderable
{

    /**
     * @var ClassName
     */
    public $class;

    public $extends;

    public $interfaces = array();

    public $uses = array();

    public $methods = array();

    public $consts = array();

    public $properties = array();

    public $staticVars = array();


    /**
     * @var boolean final class?
     */
    public $final;

    /**
     * Registered trait
     */
    public $traits = array();

    public $usedClasses = array();

    /**
     * constructor create a new class template object
     *
     * @param string $className
     *
     * a sample options:
     *
     * $t = new ClassDeclare('NewClassFoo')
     *
     */
    public function __construct($className)
    {
        $this->setClass($className);
    }

    public function setClass($className)
    {
        $this->class = new ClassName($className);
    }

    public function in($namespace)
    {
        $this->class->setNamespace($namespace);
        return $this;
    }


    public function useClass($className, $as = null)
    {
        if ($as) {
            if (isset($this->usedClasses[$as])) {
                return;
            }
            $this->usedClasses[$as] = $className;
        } else {
            if (isset($this->usedClasses[$className])) {
                return;
            }
            $this->usedClasses[$className] = $className;
        }
        $this->uses[] = new UseClass($className, $as);
    }


    /**
     * Add extends property
     *
     * @param boolean $useAlias means append 'use' statement automatically.
     */
    public function extendClass($className, $useAlias = true)
    {
        if ($className[0] === '\\' && $useAlias) {
            $className = ltrim($className, '\\');
            $this->useClass($className);

            $_p = explode('\\', $className);
            $shortClassName = end($_p);
            $this->extends = new ClassName($shortClassName);
        } else {
            $this->extends = new ClassName($className);
        }
    }

    public function implementInterface($interface)
    {
        $class = new ClassName($interface);
        $this->useClass($interface);
        $this->interfaces[] = $class;
    }

    public function implementClass($className)
    {
        $class = new ClassName($className);
        $this->useClass($className);
        $this->interfaces[] = $class;
    }

    public function addMethod($scope, $methodName, array $arguments = array(), $body = array(), array $bodyArguments = array())
    {
        $method = new ClassMethod($methodName, $arguments, $body, $bodyArguments);
        $method->setScope($scope);
        $this->methods[$methodName] = $method;
        return $method;
    }

    public function addMethodObject(ClassMethod $method)
    {
        $this->methods[$method->getName()] = $method;
    }

    public function addConst($name, $value)
    {
        $this->consts[] = new ClassConst($name, $value);
    }

    public function addConstObject(ClassConst $const)
    {
        $this->consts[] = $const;
    }

    public function addConsts($array)
    {
        foreach ($array as $name => $value) {
            $this->consts[] = new ClassConst($name, $value);
        }
    }

    public function addProperty($name, $value, $scope = 'public')
    {
        $this->properties[] = new ClassProperty($name, $value, $scope);
        return $this;
    }

    public function addPublicProperty($name, $value)
    {
        return $this->addProperty($name, $value, 'public');
    }

    public function addProtectedProperty($name, $value)
    {
        return $this->addProperty($name, $value, 'protected');
    }

    public function addPrivateProperty($name, $value)
    {
        return $this->addProperty($name, $value, 'private');
    }


    public function addStaticVar($name, $value, $scope = 'public')
    {
        $this->staticVars[] = new ClassStaticVariable($name, $value, $scope);
        return $this;
    }


    /**
     * Returns the short class name
     *
     * @return string short class name
     */
    public function getShortClassName()
    {
        return $this->class->getName();
    }

    public function getClassName()
    {
        return $this->class->getFullName();
    }

    public function render(array $args = array())
    {
        $lines = array(); // Add an option to render with a php tag
        if ($this->class->namespace) {
            $lines[] = 'namespace ' . $this->class->namespace . ';';
        }

        // When there is no namespace, we should skip the first-level class use statement.
        if ($this->uses) {
            foreach ($this->uses as $u) {
                // If we are not in a namespace, just skip these one component use statement
                if (!$this->class->namespace && count($u->getComponents()) == 1) {
                    continue;
                }
                $lines[] = $u->render();
            }
        }

        $classDeclare = ($this->final ? 'final ' : '')
            . 'class ' . $this->class->name
            ;

        $lines[] = $classDeclare;
        if ($this->extends) {
            $lines[] = Indenter::indent(1) . 'extends ' . $this->extends->render();
        }
        if ($this->interfaces) {
            $lines[] = Indenter::indent(1) . 'implements ' . implode(', ', array_map(function ($class) {
                    return $class->name;
                }, $this->interfaces));
        }

        $block = new BracketedBlock;
        foreach ($this->traits as $trait) {
            $block[] = $trait;
        }

        foreach ($this->consts as $const) {
            $block[] = $const;
        }

        foreach ($this->staticVars as $var) {
            $block[] = $var;
        }

        foreach ($this->properties as $property) {
            $block[] = $property;
        }

        foreach ($this->methods as $method) {
            $method->getBlock()->setIndentLevel(1);
            $block[] = $method;
        }
        $lines[] = $block->render($args);
        return implode("\n", $lines);
    }

    public function getPsr0ClassPath()
    {
        return str_replace('\\', DIRECTORY_SEPARATOR, $this->class->getFullName()) . '.php';
    }

    public function makeFinal()
    {
        $this->final = true;
    }

    public function requireAt($path, array $args = array())
    {
        $code = "<?php\n" . $this->render($args);
        if (file_put_contents($path, $code) === false) {
            return false;
        }
        require $path;
        return $path;

    }

    public function generateAt($path, array $args = array())
    {
        $code = "<?php\n" . $this->render($args);
        if (file_put_contents($path, $code) === false) {
            return false;
        }
        return $path;
    }

    public function generatePsr4ClassUnder($directory, array $args = array())
    {
        $className = $this->class->name;

        // translate psr4 class map to actual directory
        if (is_array($directory)) {
            $fullClassName = $this->class->getFullName();
            foreach ($directory as $nsprefix => $nsdir) {
                // found matched namesspace
                if (strpos($fullClassName, $nsprefix) === 0) {
                    $className = ltrim(substr($fullClassName, strlen($nsprefix)), '\\');
                    $directory = rtrim($nsdir, DIRECTORY_SEPARATOR);
                    continue;
                }
            }
            if (is_array($directory)) {
                throw new Exception("Can't translate class name into corresponding directory.");
            }
        }

        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        // TODO: import copyright text
        $code = "<?php\n" . $this->render($args);
        $path = $directory . DIRECTORY_SEPARATOR . $className . '.php';
        if ($dir = dirname($path)) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        if (file_put_contents($path, $code) === false) {
            return false;
        }
        return $path;
    }

    /**
     * for Foo\Bar class,
     *
     * $this->generatePsr0ClassUnder('src');
     *
     * will generate class at src/Foo/Bar.php
     */
    public function generatePsr0ClassUnder($directory, array $args = array())
    {
        $code = "<?php\n" . $this->render($args);
        $classPath = $this->getPsr0ClassPath();
        $path = $directory . DIRECTORY_SEPARATOR . $classPath;
        if ($dir = dirname($path)) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        if (file_put_contents($path, $code) === false) {
            return false;
        }
        return $path;
    }


    /**
     * This method was used for generating filename for class cache.
     * SHOULD BE DEPRECATED
     */
    public function getSplFilePath()
    {
        return str_replace('\\', '_', $this->class->getFullName());
    }

    public function addTrait(ClassTrait $trait)
    {
        $this->traits[] = $trait;
    }

    public function useTrait($class)
    {
        $classes = func_get_args();
        $self = $this;
        $classes = array_map(function ($fullClassName) use ($self) {
            // split classnames into "use" statement 
            $p = explode('\\', ltrim($fullClassName, '\\'));
            $className = end($p);
            if (count($p) > 1) {
                $this->useClass($fullClassName);
            }
            return $className;
        }, $classes);
        $trait = new ClassTrait($classes);
        $this->addTrait($trait);
        return $trait;
    }
}


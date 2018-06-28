<?php
namespace ClassTemplate;
use Exception;
use ReflectionClass;
use ReflectionObject;
use CodeGen\UserClass;
use CodeGen\Renderable;

class ClassFile extends UserClass
{
    public $templateFile;
    public $templateDirs;
    public $options = array();

    /**
     * constructor create a new class template object
     *
     * @param string $className
     * @param array $options 
     *
     * a sample options:
     * 
     * $t = new ClassTemplate('NewClassFoo',[
     *   'template_dirs' => [ path1, path2 ],
     *   'template' => 'Class.php.twig',
     *   'template_args' => [ ... predefined template arguments ],
     *   'twig' => [ 'cache' => false, ... ]
     * ])
     *
     */
    public function __construct($className, array $options = array())
    {
        parent::__construct($className);
        $this->setOptions($options);
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function setOption($key, $val) {
        $this->options[$key] = $val;
    }

    public function render(array $args = array())
    {
        return "<?php\n" . parent::render($args);
    }

    public function writeTo($file)
    {
        return file_put_contents($file, $this->render());
    }

    public function getSplFilePath()
    {
        return str_replace('\\', DIRECTORY_SEPARATOR, ltrim($this->class->getFullName(),'\\'));
    }

    public function load() {
        $tmpname = tempnam('/tmp', $this->getSplFilePath());
        if (file_put_contents($tmpname, $this->render()) != false) {
            return require $tmpname;
        }
        throw new Exception("Can not load class file $tmpname");
    }
}


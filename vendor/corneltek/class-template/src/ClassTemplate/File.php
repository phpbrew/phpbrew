<?php
namespace ClassTemplate;
use Exception;
use ReflectionClass;
use ReflectionObject;
use CodeGen\Renderable;
use CodeGen\Block;

class File extends Block
{

    public function render(array $args = array())
    {
        return "<?php\n" . parent::render($args);
    }

    public function writeTo($file)
    {
        return file_put_contents($file, $this->render());
    }

}



<?php
namespace ClassTemplate;
use Exception;
use ReflectionClass;
use ReflectionObject;
use ClassTemplate\ClassTrait;
use CodeGen\Renderable;
use ClassTemplate\ClassFile;

class TemplateClassFile extends ClassFile implements Renderable
{
    /**
     * @var TemplateView object.
     */
    protected $view;

    public $templateFile;
    public $templateDirs;
    public $options = array();
    public $msgIds = array();

    public function __construct($className, array $options = array())
    {
        if( !isset($options['template_dirs']) ) {
            $ro = new ReflectionObject($this);
            $dir = dirname($ro->getFilename()) . DIRECTORY_SEPARATOR . 'Templates';
            $options['template_dirs'] = array($dir);
        }
        if( !isset($options['template']) ) {
            $options['template'] = 'Class.php.twig';
        }

        $this->options = $options;
        $this->templateFile = $options['template'];
        $this->templateDirs = $options['template_dirs'];
        $this->setClass($className);

        $this->view = new TemplateView($this->templateDirs, 
            (isset($options['twig']) ? $options['twig'] : array()),
            (isset($options['template_args']) ? $options['template_args'] : array())
        );
        $this->view->class = $this;
    }

    public function __set($n,$v) {
        $this->view->__set($n,$v);
    }

    public function render(array $args = array())
    {
        foreach ($args as $n => $v) {
            $this->view->__set($n,$v);
        }
        $content = $this->view->renderFile($this->templateFile);
        if ( isset($this->options['trim_tag']) && strpos($content, '<?php') === 0 ) {
            return substr($content, 5);
        }
        return $content;
    }

    public function getView() {
        return $this->view;
    }

    public function setView($view) {
        $this->view = $view;
    }


    public function addMsgId($msgId) {
        $this->msgIds[] = $msgId;
    }

    public function setMsgIds($msgIds) {
        $this->msgIds = $msgIds;
    }
}

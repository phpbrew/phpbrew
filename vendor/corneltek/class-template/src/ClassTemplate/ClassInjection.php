<?php
namespace ClassTemplate;
use ReflectionClass;

class ClassInjection
{
    public $lines = array();

    /**
     * @var array Contents for injection
     */
    public $contents = array();


    /**
     * @var integer Original content line length inside the boundary.
     */
    public $contentLength = 0;


    /**
     * @var string Boundary string
     */
    public $boundary;


    /**
     * @var integer boundary start line
     */
    public $boundaryStartLine;


    /**
     * @var integer boundary end line
     */
    public $boundaryEndLine;


    /**
     * @var mixed target class object.
     */
    public $targetClass;


    /**
     * @var string targe class file name
     */
    public $filename;



    /**
     *
     * @param mixed target object
     *
     */
    public function __construct($class)
    {
        $this->targetClass = $class;
        $this->reflection = new ReflectionClass($class);
        $this->filename = $this->reflection->getFilename();
    }


    public function replaceContent($content)
    {
        $this->contents = array($content);
    }


    public function appendContent($content) 
    {
        $this->contents[] = $content;
    }

    public function getBoundary() 
    {
        if($this->boundary)
            return $this->boundary;
        $content = join("\n",$this->contents);
        $this->boundary = md5($content);
        return $this->boundary;
    }

    public function read() 
    {
        $filename = $this->reflection->getFilename();

        $this->lines    = array();
        $this->contents = array();
        $this->lines    = explode("\n",file_get_contents($filename));
        $this->contentLength = 0;
        $inBoundary = false;
        for ( $i = 0; $i < count($this->lines); $i++ ) {
            $line = $this->lines[$i];
            // parse for start boundary
            if( preg_match('/^\s*#boundary start (\w+)/',$line,$regs) ) {
                $inBoundary = true;
                $this->boundary = $regs[1];
                $this->boundaryStartLine = $i + 1;
            }
            elseif( preg_match('/^\s*#boundary end (\w+)/',$line,$regs) ) {
                $inBoundary = false;
                $this->boundaryEndLine = $i + 1;
            }
            elseif( $inBoundary ) {
                $this->contentLength++;
                $this->contents[] = $line;
            }
        }
    }


    /**
     * Returns new inner content
     */
    public function buildContent()
    {
        $contents = $this->contents;
        array_unshift( $contents, '#boundary start ' . $this->getBoundary() );
        array_push(    $contents, '#boundary end ' . $this->getBoundary() );
        return $contents;
    }

    public function removeContent()
    {
        $this->contents = array();
    }

    public function write() {
        if($this->boundaryStartLine && $this->boundaryEndLine ) {
            array_splice($this->lines, $this->boundaryStartLine - 1, $this->contentLength + 2, $this->buildContent() );
            file_put_contents($this->filename, join("\n",$this->lines) );
        }
        else {
            $endline = $this->reflection->getEndLine();
            array_splice($this->lines,$endline - 1,0, $this->buildContent() );
            file_put_contents( $this->filename, join("\n",$this->lines) );
        }

        // re-read content to update boundary information
        $this->read();
    }

    public function __toString()
    {
        return join("\n",$this->lines);
    }
}


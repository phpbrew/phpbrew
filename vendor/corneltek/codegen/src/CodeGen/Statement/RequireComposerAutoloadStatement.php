<?php
namespace CodeGen\Statement;

use CodeGen\Renderable;

class RequireComposerAutoloadStatement extends RequireStatement
{
    /**
     * @param array $prefixes lookup prefixes
     */
    public function __construct(array $prefixes = array())
    {
        $prefixes[] = getcwd();
        foreach ($prefixes as $prefix) {
            $path = $prefix . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
            if (file_exists($path)) {
                $this->expr = $path;
                break;
            }
        }
    }

    public function render(array $args = array())
    {
        if ($this->expr instanceof Renderable) {
            return 'require ' . $this->expr->render($args) . ';';
        } else {
            return 'require ' . var_export($this->expr, true) . ';';
        }
    }

}




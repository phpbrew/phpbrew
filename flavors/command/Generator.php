<?php 
namespace command;
use GenPHP\Flavor\BaseGenerator;
use GenPHP\Flavor\FlavorDirectory;
use GenPHP\Path;
use Exception;

class Generator extends BaseGenerator
{

    public function brief() 
    {
        return "phpbrew command generator";
    }

    /**
     * generate new flavor 
     *
     * @param string $name flavor name, lower case, alphabets
     * @param string $path your code base path
     */
    public function generate($commandName)
    {
        $commandClass = ucfirst($commandName) . 'Command';
        $commandFile = 'src' . DIRECTORY_SEPARATOR . 'PhpBrew' . DIRECTORY_SEPARATOR . 'Command' . DIRECTORY_SEPARATOR . $commandClass . '.php';
        $this->render( 'Command.php.twig', $commandFile, array(
            'commandClass' => $commandClass,
            'brief' => 'new command',
        ));
    }
}

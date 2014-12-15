<?php
namespace PhpBrew\Patch;

use PhpBrew\Patch\Patch;
use PhpBrew\Buildable;
use CLIFramework\Logger;

/**
 * Applies patches to a file using regexp.
 * You can customize replacement rules of a patch by passing
 * RegexpPatchRule objects to this class.
 */
class RegexpPatch implements Patch
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Buildable
     */
    private $build;

    /**
     * @var array
     */
    private $paths;

    /**
     * @var array
     */
    private $rules;

    /**
     * @var string
     */
    private $backupFileSuffix;

    public function __construct(Logger $logger, Buildable $build, array $paths, array $rules)
    {
        $this->logger = $logger;
        $this->build = $build;
        $this->paths = $paths;
        $this->rules = $rules;
    }

    public function enableBackup()
    {
        $this->backupFileSuffix = '.bak';
    }

    public function apply()
    {
        foreach ($this->paths as $relativePath) {
            $absolutePath = $this->build->getSourceDirectory() . DIRECTORY_SEPARATOR . $relativePath;
            $contents = $this->read($absolutePath);
            $this->backup($absolutePath, $contents);
            $newContents = $this->applyRules($contents);
            $this->write($absolutePath, $newContents);
        }
    }

    private function read($path)
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException('Failed to read '. $path);
        }

        return $contents;
    }

    protected function backup($path, $contents)
    {
        if ($this->backupFileSuffix && file_put_contents($path . $this->backupFileSuffix, $contents) === false) {
            throw new \RuntimeException('Failed to write ' . $path);
        }
    }

    private function applyRules($contents)
    {
        foreach ($this->rules as $rule) {
            $contents = $rule->apply($contents);
        }
        return $contents;
    }

    private function write($path, $contents)
    {
        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException('Failed to write ' . $path);
        }
    }
}


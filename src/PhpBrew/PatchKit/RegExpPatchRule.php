<?php
namespace PhpBrew\PatchKit;
use PhpBrew\Buildable;
use CLIFramework\Logger;


/**
 * RegExpPatchRule implements a pcre_replace based patch rule
 */
class RegExpPatchRule implements PatchRule
{
    private $files;


    /**
     * @var string the regexp pattern 
     */
    private $pattern;

    /**
     * @var string the replacement
     */
    private $replacement;

    /**
     * @var callable|boolean
     */
    private $predictor;


    /**
     * @param string $files
     */
    public function __construct(array $files)
    {
        $this->files       = $files;
    }


    /**
     * This method build up the predicator
     */
    public function allOf(array $patterns)
    {
        $this->predicator = function($line) use ($patterns) {
            foreach ($patterns as $pattern) {
                if (!preg_match($pattern, $line)) {
                    return false;
                }
            }
            return true;
        };
        return $this;
    }

    public function anyOf(array $patterns)
    {
        if (count($patterns) === 0) {
            $this->predicator = true;
        }
        $this->predicator = function($line) use ($patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    return true;
                }
            }
            return false;
        };
        return $this;
    }

    public function always()
    {
        $this->predicator = true;
        return $this;
    }


    public function replaces($pattern, $replacement)
    {
        $this->pattern = $pattern;
        $this->replacement = $replacement;
        return $this;
    }


    static public function files($files)
    {
        return new self((array)$files);
    }


    protected function applyLines(array $lines, &$patched)
    {
        $size = count($lines);
        for ($i = 0; $i < $size; ++$i) {
            if ($this->predicator === true || call_user_func($this->predicator, $lines[$i])) {
                $lines[$i] = preg_replace($this->pattern, $this->replacement, $lines[$i], -1, $count);
                $patched += $count;
            }
        }
        return implode($lines, PHP_EOL);
    }

    /**
     * This method can only be used for text format files.
     *
     * @param string $content the target of the text content.
     */
    protected function applyTextContent($content, &$patched)
    {
        // may use file() ?
        return $this->applyLines(preg_split("/(?:\r\n|\n|\r)/", $content), $patched);
    }

    public function backup(Buildable $build, Logger $logger)
    {
        foreach ($this->files as $file) {
            $path = $build->getSourceDirectory() . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($path)) {
                $logger->error("file $path doesn't exist in the build directory.");
                continue;
            }
            $this->backupFile($path);
        }
    }

    protected function backupFile($path)
    {
        $bakPath = $path . '.' . time() . '.bak';
        copy($path, $bakPath);
    }

    public function apply(Buildable $build, Logger $logger)
    {
        $patched = 0;
        foreach ($this->files as $file) {
            $path = $build->getSourceDirectory() . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($path)) {
                $logger->error("file $path doesn't exist in the build directory.");
                continue;
            }
            if ($content = file_get_contents($path)) {
                $content = $this->applyTextContent($content, $patched);
                if (false === file_put_contents($path, $content)) {
                    $logger->error("Patch on $path write failed.");
                }
            }
        }
        return $patched;
    }
}

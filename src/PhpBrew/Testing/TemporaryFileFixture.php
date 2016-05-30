<?php

namespace PhpBrew\Testing;

use PhpBrew\Config;

class TemporaryFileFixture
{
    private $caller;
    private $sourcePath;
    private $temporaryDirectory;

    /**
     * $caller must be a subclass of PHPUnit_Framework_TestCase.
     *
     * @param object $caller     the object which creates this object.
     * @param string $sourcePath the path of a source file.
     */
    public function __construct($caller, $sourcePath)
    {
        $this->caller = $caller;
        $this->sourcePath = $sourcePath;
        $this->temporaryDirectory = Config::getTempFileDir();
    }

    /**
     * Sets the temprary directory where this object puts temprary files into.
     */
    public function setTemporaryDirectory($dir)
    {
        $this->temporaryDirectory = $dir;
    }

    /**
     * Returns the path of the temporary directory.
     */
    public function getTemporaryDirectory()
    {
        return $this->temporaryDirectory;
    }

    /**
     * Calls the callback function after creating a temporary file, which
     * is a copy of the source file. The copy is automatically removed, so
     * you don't have to delete it.
     * The 1st argument of the callback function is an object which creates
     * this object.
     * The 2nd argument of the callback function is a destination file path.
     *
     * @param string   $destFileName the filename of a copy of the source file.
     * @param callable $callback     the function called after creating a destination file.
     *
     * @example
     * $fixture = new TemporaryFileFixture($this, '/tmp/Makefile.in');
     * $fixture->withFile('/tmp/Makefile', function($self, $destFilePath) {
     *   // do something
     * });
     */
    public function withFile($destFileName, $callback)
    {
        $contents = file_get_contents($this->sourcePath);
        $destPath = $this->temporaryDirectory.DIRECTORY_SEPARATOR.$destFileName;

        $this->caller->assertFileExists($this->sourcePath);
        file_put_contents($destPath, $contents);
        $this->caller->assertFileExists($destPath);

        try {
            $callback($this->caller, $destPath);
        } catch (\Exception $e) {
            @unlink($destPath);
            $this->caller->fail($e->getMessage());
        }
    }
}

<?php
namespace PhpBrew\PatchKit;
use PhpBrew\Buildable;
use PhpBrew\Downloader\DownloadFactory;
use CLIFramework\Logger;
use GetOptionKit\OptionResult;


/**
 * DiffPatchRule implements a diff based patch rule
 */
class DiffPatchRule
{
    protected $diffFile;

    protected $strip = 0;

    public function __construct($diffFile)
    {
        $this->diffFile = $diffFile;
    }

    public function strip($level)
    {
        $this->strip = $level;
        return $this;
    }

    static public function from($diffFile)
    {
        return new self($diffFile);
    }

    public function backup(Buildable $build, Logger $logger)
    {

    }

    public function apply(Buildable $build, Logger $logger)
    {
        $dir = $build->getSourceDirectory();

        $logger->info("---> Fetching patch from {$this->diffFile} ...");

        $content = file_get_contents($this->diffFile);
        $basename = basename($this->diffFile);
        if (!$basename) {
            $basename = tempnam("/tmp", "patch");
        }
        $diffFile = $dir . DIRECTORY_SEPARATOR . $basename;
        if (false === file_put_contents($diffFile, $content)) {
            $logger->error("Can not write file to $diffFile");
            return false;
        }

        $logger->info("---> Patching from {$diffFile} ...");
        $lastline = system("patch --directory " . escapeshellarg($dir) . " --backup -p{$this->strip} < " . escapeshellarg($diffFile), $retval);
        if ($retval !== 0) {
            $logger->error("patch failed: $lastline");
            return false;
        }
        return 1;
    }

}
    

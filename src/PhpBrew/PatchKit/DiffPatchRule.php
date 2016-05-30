<?php

namespace PhpBrew\PatchKit;

use PhpBrew\Buildable;
use CLIFramework\Logger;

/**
 * DiffPatchRule implements a diff based patch rule.
 */
class DiffPatchRule
{
    protected $diffFile;

    protected $strip = 0;

    protected $sha256;

    public function __construct($diffFile)
    {
        $this->diffFile = $diffFile;
    }

    public function strip($level)
    {
        $this->strip = $level;

        return $this;
    }

    public function sha256($checksum)
    {
        $this->sha256 = $checksum;

        return $this;
    }

    public static function from($diffFile)
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
            $basename = tempnam('/tmp', 'patch');
        }

        if ($this->sha256) {
            $contentSha256 = hash('sha256', $content);

            $logger->debug("Checking checksum {$contentSha256} != {$this->sha256}");
            if ($this->sha256 !== $contentSha256) {
                $logger->error("Checksum mismatched {$contentSha256} != {$this->sha256}");

                return;
            }
        }

        $diffFile = $dir.DIRECTORY_SEPARATOR.$basename;
        if (false === file_put_contents($diffFile, $content)) {
            $logger->error("Can not write file to $diffFile");

            return false;
        }

        $logger->info("---> Patching from {$diffFile} ...");
        $lastline = system('patch --forward --directory '.escapeshellarg($dir)." --backup -p{$this->strip} < ".escapeshellarg($diffFile), $retval);
        if ($retval !== 0) {
            $logger->error("patch failed: $lastline");

            return false;
        }

        return 1;
    }
}

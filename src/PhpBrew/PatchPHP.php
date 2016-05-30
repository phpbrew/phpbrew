<?php

namespace PhpBrew;

/**
 * A simple wrapper for `patch` command.
 *
 * <code>
 * $patchphp = new PatchPHP('patch-name');
 * $patchphp->fetchDiff( 'http://remote.url/patch-1.diff' );
 * $patchphp->patch();
 * </code>
 *
 * OR:
 * <code>
 * $patchphp = new PatchPHP('patch-name');
 * $patchphp->diff =<<<EOS
 *
 * ... diff content
 *
 * EOS;
 * $patchphp->patch();
 * </code>
 */
class PatchPHP
{
    public $diff;
    public $patchName;

    public function __construct($patchName = null)
    {
        $this->patchName = $patchName;
        $this->diff = '';
    }

    public function getPatchFilename()
    {
        return ($this->patchName ?: uniqid()).'.patch';
    }

    /**
     * fetch remote diff.
     *
     * @param string $url
     */
    public function fetchDiff($url)
    {
        $this->diff = file_get_contents($url);
    }

    /**
     * patch file.
     */
    public function patch()
    {
        if ($this->diff) {
            $patchFile = $this->getPatchFilename();
            file_put_contents($patchFile, $this->diff);
            system("patch < $patchFile");

            // clean up patch File
            unlink($patchFile);
        }
    }
}

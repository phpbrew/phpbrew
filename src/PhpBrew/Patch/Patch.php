<?php
namespace PhpBrew\Patch;

/**
 * A common interface of patch utility classes.
 */
interface Patch
{
    /**
     * Enables a backup before applying patches.
     * Call this method when you want to preserve an original file.
     */
    public function enableBackup();

    /**
     * Applies patches to a file.
     */
    public function apply();
}

<?php
namespace PhpBrew;

/*
A simple wrapper for `patch` command.

$patchphp = new PatchPHP;
$patchphp->diff =<<<EOS

... diff content

EOS;
$patchphp->patch( 'path/to/file' );
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
        return ($this->patchName ?: uniqid()) . '.patch';
    }

    public function patch($file)
    {
        if( $this->diff ) {
            $patchFile = $this->getPatchFilename();
            file_put_contents( $patchFile , $this->diff );
            system( "patch $file < $patchFile" );
        }
    }

}


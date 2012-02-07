<?php



namespace PhpBrew;

class PkgConfig
{
    static function getPrefix($package)
    {
        $cmd = 'pkg-config --variable=prefix ' . $package;
        $process = new Process( $cmd );
        $process->run();
        return trim($process->getOutput());
    }
}

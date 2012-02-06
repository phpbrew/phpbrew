<?php
namespace PhpBrew\Command;
use Exception;

class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

    public function execute($version)
    {
        $versions = \PhpBrew\PhpStas::getVersions();
        if( ! isset($versions[$version] ) )
            throw new Exception("Version $version not found.");

        $url = $versions[ $version ];

        var_dump( $url ); 
        

    }
}





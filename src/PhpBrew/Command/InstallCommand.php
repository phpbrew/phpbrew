<?php
namespace PhpBrew\Command;
use Exception;

class InstallCommand extends \CLIFramework\Command
{
    public function brief() { return 'install php'; }

    public function execute($version)
    {
        $logger = $this->getLogger();
        $versions = \PhpBrew\PhpStas::getVersions();
        if( ! isset($versions[$version] ) )
            throw new Exception("Version $version not found.");

        $url = $versions[ $version ];

        $home = getenv('HOME') . DIRECTORY_SEPARATOR . '.phpbrew';
        if( ! file_exists($home) )
            mkdir( $home );
        chdir( $home );

        $parts = parse_url($url);
        $basename = basename( $parts['path'] );

        $logger->info("Downloading $url");
        system( 'curl -# -O ' . $url );





    }
}





<?php
namespace PhpBrew\Command;
use PhpBrew\Config;

class ListCommand extends \CLIFramework\Command
{
    public function brief() { return 'list installed PHP versions'; }

    public function execute()
    {
        $versions = \PhpBrew\Config::getInstalledPhpVersions();

        // var_dump( $versions ); 
        echo "Installed versions:\n";
        foreach( $versions as $version ) {
            $versionPrefix = Config::getVersionBuildPrefix($version);

            printf('  %-16s  (%-10s)', $version, $versionPrefix);

            if( file_exists($versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants') ) {
                $info = unserialize(file_get_contents( $versionPrefix . DIRECTORY_SEPARATOR . 'phpbrew.variants'));

                echo "\n    Variants: ";

                foreach( $info['enabled_variants'] as $k => $v ) {
                    echo '+' . $k;
                    if (! is_bool($v)) {
                        echo '=' . $v . ' ';
                    }
                }
                echo " " . '-' . join('-', array_keys($info['disabled_variants']));
                echo " " . '-- ' . join(' ', $info['extra_options']);
            }

            echo "\n";
        }
    }
}



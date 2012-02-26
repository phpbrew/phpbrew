<?php
namespace PhpBrew\Command;
use PhpBrew\Variants;

class VariantsCommand extends \CLIFramework\Command
{


    public function brief() { return 'list php variants'; }

    public function usage() { 
        return 'phpbrew variants [php-version]';
    }

    public function execute($version = null)
    {
        $variants = new Variants;
        $list = $variants->getVariantNames();

        echo "Variants\n";
        foreach( $list as $feature ) {
            echo "    $feature\n";
        }

        echo "\n";
        echo "Example:\n";
        echo "\n";
        echo "    phpbrew install php-5.3.10 +mysql +pdo\n";
        echo "    phpbrew install php-5.3.10 +mysql +pdo +apxs2\n";
        echo "    phpbrew install php-5.3.10 +mysql +pdo +apxs2=/usr/bin/apxs2\n";
        echo "\n";
        echo "\n";
    }

}





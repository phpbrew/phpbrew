<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use PhpBrew\VariantBuilder;

class VariantsCommand extends Command
{
    public function brief()
    {
        return 'List php variants';
    }

    public function usage()
    {
        return 'phpbrew variants [php-version]';
    }

    public function wrapLine($line, $prefix = '  ', $indent = '  ')
    {
        $lineX = 0;
        $newLine = $prefix;

        for ($i = 0; $i < strlen($line); $i++ && $lineX++) {
            $c = $line[$i];
            $newLine .= $c;

            if ($lineX > 68 && $c === ' ') {
                $newLine .= "\n" . $indent;
                $lineX = 0;
            }
        }

        return $newLine;
    }

    public function execute($version = null)
    {
        $variants = new VariantBuilder();
        $list = $variants->getVariantNames();
        sort($list);

        echo "Variants: \n";
        echo $this->wrapLine(implode(', ', $list)) , "\n";
        echo "\n\n";

        echo "Virtual variants: \n";

        foreach ($variants->virtualVariants as $name => $subvars) {
            echo $this->wrapLine("$name: " . implode(', ', $subvars)) , "\n";
        }

        echo "\n\n";

        echo "Using variants to build PHP:\n";
        echo "\n";
        echo "  phpbrew install php-5.3.10 +default\n";
        echo "  phpbrew install php-5.3.10 +mysql +pdo\n";
        echo "  phpbrew install php-5.3.10 +mysql +pdo +apxs2\n";
        echo "  phpbrew install php-5.3.10 +mysql +pdo +apxs2=/usr/bin/apxs2\n";
        echo "\n\n";
    }
}

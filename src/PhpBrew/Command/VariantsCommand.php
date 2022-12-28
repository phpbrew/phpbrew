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
                $newLine .= PHP_EOL . $indent;
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

        echo "Variants: " . PHP_EOL;
        echo $this->wrapLine(implode(', ', $list)) , PHP_EOL;
        echo PHP_EOL, PHP_EOL;

        echo "Virtual variants: ", PHP_EOL;

        foreach ($variants->virtualVariants as $name => $subvars) {
            echo $this->wrapLine("$name: " . implode(', ', $subvars)) , PHP_EOL;
        }

        echo PHP_EOL, PHP_EOL;

        echo "Using variants to build PHP:", PHP_EOL;
        echo PHP_EOL;
        echo "  phpbrew install php-5.3.10 +default", PHP_EOL;
        echo "  phpbrew install php-5.3.10 +mysql +pdo", PHP_EOL;
        echo "  phpbrew install php-5.3.10 +mysql +pdo +apxs2", PHP_EOL;
        echo "  phpbrew install php-5.3.10 +mysql +pdo +apxs2=/usr/bin/apxs2", PHP_EOL;
        echo PHP_EOL, PHP_EOL;
    }
}

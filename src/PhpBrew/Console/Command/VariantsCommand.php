<?php

namespace PhpBrew\Console\Command;

use PhpBrew\VariantBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VariantsCommand extends Command
{
    public function wrapLine($line, $prefix = "  ", $indent = "  ")
    {
        $lineX = 0;
        $newLine = $prefix;
        for($i = 0; $i < strlen($line) ; $i++ && $lineX++ ) {
            $c = $line[$i];
            $newLine .= $c;
            if( $lineX > 68 && $c === ' ' ) {
                $newLine .= "\n" . $indent;
                $lineX = 0;
            }
        }
        return $newLine;
    }

    protected function configure()
    {
        $this
            ->setName('variants')
            ->setDescription('List php variants.')
            ->setDefinition(array(
                new InputArgument('version', InputArgument::OPTIONAL, 'The php version to download'),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $variants = new VariantBuilder;
        $list = $variants->getVariantNames();
        sort($list);

        echo "Variants: \n";
        echo $this->wrapLine(join(', ',$list)) , "\n";
        echo "\n\n";

        echo "Virtual variants: \n";
        foreach( $variants->virtualVariants as $name => $subvars ) {
            echo $this->wrapLine("$name: " . join(', ', $subvars)) , "\n";
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





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

        $output->writeln('Variants:');
        $output->writeln($this->wrapLine(join(', ',$list)));
        $output->writeln('');

        $output->writeln('Virtual variants:');
        foreach( $variants->virtualVariants as $name => $subvars ) {
            $output->writeln($this->wrapLine("$name: " . join(', ', $subvars)));
        }
        $output->writeln('');

        $output->writeln('Using variants to build PHP:');
        $output->writeln('  phpbrew install php-5.3.10 +default');
        $output->writeln('  phpbrew install php-5.3.10 +mysql +pdo');
        $output->writeln('  phpbrew install php-5.3.10 +mysql +pdo +apxs2');
        $output->writeln('  phpbrew install php-5.3.10 +mysql +pdo +apxs2=/usr/bin/apxs2');

        $output->writeln('');
    }

}





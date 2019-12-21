<?php

namespace PHPBrew\Console\Command;

use PHPBrew\BuildFinder;
use PHPBrew\Config;
use PHPBrew\VariantParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListCommand extends Command
{
    protected static $defaultName = 'list';

    protected function configure()
    {
        $this
            ->setDescription('List installed PHPs')
            ->addOption('dir', 'd', null, 'Show PHP directories')
            ->addOption('variants', null, null, 'Show used variants')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $builds = BuildFinder::findInstalledBuilds();
        $currentBuild = Config::getCurrentPhpName();

        if (empty($builds)) {
            $output->writeln('<note>Please install at least one PHP with your preferred version.</note>');
        }

        if ($currentBuild === false or !in_array($currentBuild, $builds)) {
            $output->writeln('* (system)');
        }

        foreach ($builds as $build) {
            $versionPrefix = Config::getVersionInstallPrefix($build);

            $output->write('<options=bold>');
            $output->write($currentBuild === $build ? '*' : ' ');
            $output->writeln(' ' . $build . '</>');

            if ($input->getOption('dir')) {
                $output->writeln(sprintf('    Prefix:   %s', $versionPrefix));
            }

            // TODO: use Build class to get the variants
            if ($input->getOption('variants') && file_exists($versionPrefix . '/phpbrew.variants')) {
                $info = unserialize(file_get_contents($versionPrefix . '/phpbrew.variants'));
                $output->writeln(
                    '    Variants: '
                    . wordwrap(VariantParser::revealCommandArguments($info), 75, " \\\n              ")
                );
            }
        }

        return 0;
    }
}

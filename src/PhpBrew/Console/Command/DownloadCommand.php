<?php

namespace PhpBrew\Console\Command;

use Exception;
use PhpBrew\Config;
use PhpBrew\Variants;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\Tasks\PrepareDirectoryTask;
use PhpBrew\DirectorySwitch;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('download')
            ->setDescription('Download php.')
            ->setDefinition(array(
                new InputArgument('version', InputArgument::REQUIRED, 'The php version to download'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force extraction'),
                new InputOption('old', null, InputOption::VALUE_NONE, 'Enable old phps (less than 5.3)'),
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');

        if( ! preg_match('/^php-/', $version) )
            $version = 'php-' . $version;

        $info = PhpSource::getVersionInfo( $version, $this->options->old );
        if( ! $info)
            throw new Exception("Version $version not found.");

        $prepare = new PrepareDirectoryTask($this->logger);
        $prepare->prepareForVersion($version);

        $buildDir = Config::getBuildDir();

        $dw = new DirectorySwitch;
        $dw->cd($buildDir);

        $download = new DownloadTask($this->logger);
        $targetDir = $download->downloadByVersionString($version, $this->options->old , $this->options->force );

        if( ! file_exists( $targetDir ) ) {
            throw new Exception("Download failed.");
        }
        $this->logger->info("Done, please look at: $buildDir/$targetDir");
        $dw->back();
    }
}


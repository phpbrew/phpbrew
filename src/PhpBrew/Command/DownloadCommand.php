<?php
namespace PhpBrew\Command;
use Exception;
use PhpBrew\Config;
use PhpBrew\Variants;
use PhpBrew\PhpSource;
use PhpBrew\CommandBuilder;
use PhpBrew\Tasks\DownloadTask;
use PhpBrew\Tasks\CleanTask;
use PhpBrew\Tasks\PrepareDirectoryTask;

use CLIFramework\Command;

class DownloadCommand extends Command
{
    public function brief() { return 'download php'; }

    public function usage() 
    {
        return 'phpbrew download [php-version]';
    }

    public function options($opts)
    {
        $opts->add('f|force','Force extraction');
        $opts->add('old','enable old phps (less than 5.3)');
    }

    public function execute($version)
    {
        $info = PhpSource::getVersionInfo( $version, $this->options->old );
        if( ! $info)
            throw new Exception("Version $version not found.");

        $prepare = new PrepareDirectoryTask($this->logger);
        $prepare->prepareForVersion($version);

        $buildDir = Config::getBuildDir();

        $download = new DownloadTask($this->logger);
        $targetDir = $download->downloadByVersionString($version, $this->options->old , $this->options->force );

        if( ! file_exists( $targetDir ) ) {
            throw new Exception("Download failed.");
        }

        $this->logger->info("Done, please look at: $buildDir/$targetDir");
    }
}


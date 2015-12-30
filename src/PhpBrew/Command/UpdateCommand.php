<?php
namespace PhpBrew\Command;
use PhpBrew\Downloader\Factory as DownloadFactory;
use PhpBrew\Tasks\FetchReleaseListTask;

class UpdateCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Update PHP release source file';
    }

    public function options($opts)
    {

        $opts->add('o|old', 'List old phps (less than 5.3)');

        $opts->add('official', 'Unserialize release information from official site (using `unserialize` function).');

        DownloadFactory::addOptionsForCommand($opts);
    }

    public function execute()
    {
        $fetchTask = new FetchReleaseListTask($this->logger, $this->options);
        $releases = $fetchTask->fetch();

        foreach ($releases as $majorVersion => $versions) {
            if (strpos($majorVersion, '5.2') !== false && ! $this->options->old) {
                continue;
            }
            $versionList = array_keys($versions);
            $this->logger->writeln($this->formatter->format("{$majorVersion}: ", 'yellow')
                . count($versionList) . ' releases');
        }
        $this->logger->info('===> Done');

    }
}

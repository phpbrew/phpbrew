<?php
namespace PhpBrew\Command;
use PhpBrew\PhpSource;
use PhpBrew\Config;
use PhpBrew\Tasks\FetchReleaseListTask;

class KnownCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'List known PHP versions';
    }

    public function init()
    {
        $this->command('unstable');
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('more', 'Show more older versions');
        $opts->add('old', 'List old phps (less than 5.3)');
        $opts->add('u|update', 'Update release list');
    }

    public function execute()
    {
        $releaseListFile = Config::getPHPReleaseListPath();

        $releases = array();
        if (!file_exists($releaseListFile) || $this->options->update) {
            // Fetch
            $fetchTask = new FetchReleaseListTask($this->logger, $this->options);
            $releases = $fetchTask->fetch('feature/release-list');
        } else {
            $releases = json_decode(file_get_contents($releaseListFile), true);
        }

        foreach($releases as $majorVersion => $versions) {
            if (strpos($majorVersion, '5.2') !== false && ! $this->options->old) {
                continue;
            }
            $versionList = array_keys($versions);
            if (!$this->options->more) {
                array_splice($versionList, 8);
            }
            $this->logger->writeln($this->formatter->format("{$majorVersion}:  ", 'yellow'). join(', ', $versionList) 
                . (!$this->options->more ? ' ...' : ''));
        }
    }
}

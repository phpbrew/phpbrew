<?php

namespace PhpBrew\Command;

use CLIFramework\Command;
use GetOptionKit\OptionSpecCollection;
use PhpBrew\Config;
use PhpBrew\Downloader\DownloadFactory;
use PhpBrew\ReleaseList;
use PhpBrew\Tasks\FetchReleaseListTask;

class KnownCommand extends Command
{
    public function brief()
    {
        return 'List known PHP versions';
    }

    /**
     * @param OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('m|more', 'Show more older versions');
        $opts->add('o|old', 'List old phps (less than 5.3)');
        $opts->add('u|update', 'Update release list');

        DownloadFactory::addOptionsForCommand($opts);
    }

    public function execute()
    {
        $releaseList = new ReleaseList();

        //always fetch list from remote when --old presents, because the local file may not contain the old versions
        // and --old is seldom used.
        if (!$releaseList->foundLocalReleaseList() || $this->options->update || $this->options->old) {
            $fetchTask = new FetchReleaseListTask($this->logger, $this->options);
            $releases = $fetchTask->fetch();
        } else {
            $this->logger->info(
                sprintf(
                    'Read local release list (last update: %s UTC).',
                    gmdate(
                        'Y-m-d H:i:s',
                        filectime(Config::getPHPReleaseListPath())
                    )
                )
            );
            $releases = $releaseList->loadLocalReleaseList();
            $this->logger->info(
                'You can run `phpbrew update` or `phpbrew known --update` to get a newer release list.'
            );
        }

        foreach ($releases as $majorVersion => $versions) {
            if (version_compare($majorVersion, '5.2', 'le') && !$this->options->old) {
                continue;
            }
            $versionList = array_keys($versions);
            if (!$this->options->more) {
                array_splice($versionList, 8);
            }
            $this->logger->writeln(
                $this->formatter->format("{$majorVersion}: ", 'yellow')
                . wordwrap(implode(', ', $versionList), 80, PHP_EOL . str_repeat(' ', 5))
                . (!$this->options->more ? ' ...' : '')
            );
        }

        if ($this->options->old) {
            $this->logger->warn(
                'PHPBrew needs PHP 5.3 or above to run. build/switch to versions below 5.3 at your own risk.'
            );
        }
    }
}

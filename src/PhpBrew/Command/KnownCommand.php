<?php
namespace PhpBrew\Command;
use PhpBrew\ReleaseList;
use PhpBrew\Tasks\FetchReleaseListTask;

class KnownCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'List known PHP versions';
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
        $opts->add('m|more', 'Show more older versions');
        $opts->add('o|old', 'List old phps (less than 5.3)');
        $opts->add('u|update', 'Update release list');
        $opts->add('http-proxy:', 'The HTTP Proxy to download PHP distributions. e.g. --http-proxy=22.33.44.55:8080')
            ->valueName('proxy host')
        ;

        $opts->add('http-proxy-auth:', 'The HTTP Proxy Auth to download PHP distributions. user:pass')
            ->valueName('user:pass')
        ;

        $opts->add('connect-timeout:', 'The system aborts the command if downloading '
                . 'of the versions list not starts during this limit. This option '
                . 'overrides a value of CONNECT_TIMEOUT environment variable.')
            ->valueName('seconds')
            ;
    }

    public function execute()
    {
        $releaseList = new ReleaseList;

        $releases = array();
        if (!$releaseList->foundLocalReleaseList() || $this->options->update) {
            $fetchTask = new FetchReleaseListTask($this->logger, $this->options);
            $releases = $fetchTask->fetch();
        } else {
            $releases = $releaseList->loadLocalReleaseList();
        }

        foreach ($releases as $majorVersion => $versions) {
            if (version_compare($majorVersion, '5.2', 'le') && ! $this->options->old) {
                continue;
            }
            $versionList = array_keys($versions);
            if (!$this->options->more) {
                array_splice($versionList, 8);
            }
            $this->logger->writeln($this->formatter->format("{$majorVersion}: ", 'yellow') . wordwrap(join(', ', $versionList), 80, "\n" . str_repeat(' ',5))
                . (!$this->options->more ? ' ...' : ''));
        }
        $this->logger->info("You can run `phpbrew update` to get a newer release list.");
    }
}

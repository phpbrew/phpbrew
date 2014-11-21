<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\Extension\GithubExtensionDownloader;
use PhpBrew\Extension\PeclExtensionDownloader;
use PhpBrew\GithubExtensionList;
use PhpBrew\Tasks\FetchGithubExtensionListTask;

class KnownCommand extends \CLIFramework\Command
{

    public function usage()
    {
        return 'phpbrew [-dv, -r] ext known extension_name';
    }

    public function brief()
    {
        return 'List known versions';
    }

    /**
     * @param \GetOptionKit\OptionSpecCollection $opts
     */
    public function options($opts)
    {
    }

    public function execute($extensionName)
    {

        $extensionList = new GithubExtensionList;

        // initial local list
        if (!$extensionList->foundLocalExtensionList() || $this->options->update) {
            $fetchTask = new FetchGithubExtensionListTask($this->logger, $this->options);
            $fetchTask->fetch('master');
        }

        $githubExtension = $extensionList->exists($extensionName);
        if ($githubExtension) {
            $githubExtensionDownloader = new GithubExtensionDownloader($this->logger, $this->options);
            $versionList = $githubExtensionDownloader->knownReleases($githubExtension['owner'], $githubExtension['repository']);
        } else {
            $peclExtensionDownloader = new PeclExtensionDownloader($this->logger, $this->options);
            $versionList = $peclExtensionDownloader->knownReleases($extensionName);
        }

        $this->logger->info("\n");
        $this->logger->writeln(wordwrap(join(', ', $versionList), 80, "\n" ));

    }
}

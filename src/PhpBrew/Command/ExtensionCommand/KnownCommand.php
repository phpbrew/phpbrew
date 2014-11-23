<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\Config;
use PhpBrew\Extension\ExtensionDownloader;
use PhpBrew\ExtensionList;
use PhpBrew\Tasks\FetchExtensionListTask;

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

        $extensionList = new ExtensionList;
        // initial local list
        $extensionList->initLocalExtensionList($this->logger, $this->options);

        $provider = $extensionList->exists($extensionName);

        if ($provider) {
            $extensionDownloader = new ExtensionDownloader($this->logger, $this->options);
            $versionList = $extensionDownloader->knownReleases($provider);
            $this->logger->info("\n");
            $this->logger->writeln(wordwrap(join(', ', $versionList), 80, "\n" ));
        } else {
            $this->logger->info("Can not determine host or unsupported of $extensionName \n");
        }

    }
}

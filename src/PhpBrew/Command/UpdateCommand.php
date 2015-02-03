<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use PhpBrew\ExtensionList;
use PhpBrew\ReleaseList;
use PhpBrew\Tasks\FetchExtensionListTask;
use PhpBrew\Tasks\FetchReleaseListTask;

class UpdateCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'Update PHP release source file';
    }

    public function options($opts)
    {
        $opts->add('http-proxy:', 'The HTTP Proxy to download PHP distributions. e.g. --http-proxy=22.33.44.55:8080')
            ->valueName('proxy host')
            ;

        $opts->add('http-proxy-auth:', 'The HTTP Proxy Auth to download PHP distributions. user:pass')
            ->valueName('user:pass')
            ;

        $opts->add('official', 'Unserialize release information from official site (using `unserialize` function).');
        
        $opts->add('downloader-connect-timeout:', 'The number of seconds for '
                . 'CURLOPT_CONNECTTIMEOUT option')
            ->valueName('seconds')
            ;
    }

    public function execute($branchName = 'master')
    {
        $releaseList = new ReleaseList;
        $releases = array();

        if ($this->options->official) {
            $releases = ReleaseList::buildReleaseListFromOfficialSite();
            $releaseList->setReleases($releases);
            $releaseList->save();
        } else {
            $fetchTask = new FetchReleaseListTask($this->logger, $this->options);
            $releases = $fetchTask->fetch($branchName);
        }
        foreach($releases as $majorVersion => $versions) {
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

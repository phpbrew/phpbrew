<?php
namespace PhpBrew\Command;
use PhpBrew\Config;
use PhpBrew\ReleaseList;
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
    }

    public function execute($branchName = 'master')
    {
        $releaseList = new ReleaseList;
        $fetchTask = new FetchReleaseListTask($this->logger, $this->options);
        $releases = $fetchTask->fetch($branchName);
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

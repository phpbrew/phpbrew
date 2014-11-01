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

    public function execute($branchName = 'master')
    {
        $releaseList = new ReleaseList;
        $releases = $releaseList->fetchRemoteReleaseList($branchName);
        foreach($releases as $majorVersion => $versions) {
            if (strpos($majorVersion, '5.2') !== false && ! $this->options->old) {
                continue;
            }
            $versionList = array_keys($versions);
            $this->logger->writeln($this->formatter->format("{$majorVersion}: ", 'yellow') 
                . count($versionList) . ' releases');
        }
    }
}

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
        if (!file_exists($releaseListFile) || $this->option->update) {
            // Fetch
            $fetchTask = new FetchReleaseListTask($this->logger, $this->options);
            $releases = $fetchTask->fetch('feature/release-list');
        } else {
            $releases = json_decode(file_get_contents($releaseListFile));
        }

        var_dump( $releases ); 

        /*
        $stableVersions = PhpSource::getStableVersions($this->options->old);
        // aggregate by minor versions
        $stableVersionsByMinorNumber = array();
        foreach ($stableVersions as $version => $arg) {
            if (preg_match('#php-(5\.\d+)#',$version, $regs)) {
                $stableVersionsByMinorNumber[$regs[1]][] = str_replace('php-', '', $version);
            }
        }

        echo "Available stable versions:\n";
        foreach ($stableVersionsByMinorNumber as $minorVersion => $versions) {
            if (! $this->options->more) {
                array_splice($versions, 8);
            }
            echo $this->formatter->format("{$minorVersion}+\t", 'yellow'), join(', ', $versions), "\n";
        }
        */
    }
}

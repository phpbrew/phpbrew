<?php
namespace PhpBrew\Command;
use PhpBrew\PhpSource;

class KnownCommand extends \CLIFramework\Command
{
    public function brief()
    {
        return 'list known PHP versions';
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
        $opts->add('more', 'show more older versions');
        $opts->add('svn', 'list subversion phps');
        $opts->add('old', 'list old phps (less than 5.3)');
        $managers = PhpSource::getReleaseManagers();

        foreach ($managers as $id => $fullName) {
            $opts->add($id, "list $id phps");
        }
    }

    public function execute()
    {
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

        if ($this->options->svn) {
            $svnVersions = \PhpBrew\PhpSource::getSvnVersions();
            echo $this->formatter->format("Available svn versions:\n", 'yellow');

            foreach ($svnVersions as $version => $arg) {
                echo "  " . $version . "\n";
            }
        }

        $managers = PhpSource::getReleaseManagers();
        foreach ($managers as $id => $fullName) {
            if ($this->options->$id) {
                $versions = \PhpBrew\PhpSource::getReleaseManagerVersions($id);
                echo $this->formatter->format(
                    "Available versions from PHP Release Manager: $fullName\n",
                    'yellow'
                );

                foreach ($versions as $version => $arg) {
                    echo "  " . $version . "\n";
                }
            }
        }
    }
}

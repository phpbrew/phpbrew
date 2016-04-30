<?php
namespace PhpBrew\Command;

use Exception;
use RuntimeException;
use CLIFramework\Command;

class SelfUpdateCommand extends Command
{
    public function usage()
    {
        return 'phpbrew self-update [branch-name]';
    }

    public function brief()
    {
        return 'Self-update, default to master version';
    }

    public function arguments($args)
    {
        $args->add('branch')->suggestions(function () {
            /** TODO: maybe fetch tags and remote branches from github? */
            return array('master', 'develop');
        });
    }

    public function execute($branch = 'master')
    {
        global $argv;
        $script = realpath($argv[0]);

        if (!is_writable($script)) {
            throw new Exception("$script is not writable.");
        }

        // fetch new version phpbrew
        $this->logger->info("Updating phpbrew $script from $branch...");
        $url = "https://raw.githubusercontent.com/phpbrew/phpbrew/$branch/phpbrew";
        //download to a tmp file first
        $tempFile = tempnam(sys_get_temp_dir(), 'phpbrew_');
        if ($tempFile === false) {
            throw new RuntimeException("Fail to create temp file", 2);
        }
        chmod($tempFile, 0755);
        $lastLine = system("curl -# -L $url > $tempFile", $code);
        if ($lastLine === false || ! $code == 0) {
            throw new RuntimeException("Update Failed", 1);
        }
        //todo we can check the hash here in order to make sure we have download the phar successfully

        //move the tmp file to executable path
        $code = rename($tempFile, $script);
        if ($code === false) { //fallback to system move
            $code = system("mv -f $tempFile, $script");
            if (! $code == 0) {
                throw new RuntimeException("Update Failed", 3);
            }
        }

        $this->logger->info("Version updated.");
        system($script . ' init');
        system($script . ' --version');
    }
}

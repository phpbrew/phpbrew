<?php

namespace PHPBrew\Command;

use CLIFramework\Command;
use PHPBrew\BuildFinder;

class SystemCommand extends Command
{
    public function brief()
    {
        return 'Get or set the internally used PHP binary';
    }

    public function arguments($args)
    {
        $args->add('php version')
            ->suggestions(function () {
                return BuildFinder::findInstalledBuilds();
            });
    }

    final public function execute()
    {
        $path = getenv('PHPBREW_SYSTEM_PHP');

        if ($path !== false && $path !== '') {
            $this->logger->writeln($path);
        }
    }
}

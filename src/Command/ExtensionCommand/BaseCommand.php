<?php

namespace PHPBrew\Command\ExtensionCommand;

use CLIFramework\Command;

abstract class BaseCommand extends Command
{
    public function prepare()
    {
        parent::prepare();
        if (!getenv('PHPBREW_PHP')) {
            $this->logger->error(<<<EOF
Error: PHPBREW_PHP environment variable is not defined.
  This extension command requires you specify a PHP version from your build list.
  And it looks like you haven't switched to a version from the builds that were built with PHPBrew.
Suggestion: Please install at least one PHP with your preferred version and switch to it.
EOF
            );

            return false;
        }

        return true;
    }
}

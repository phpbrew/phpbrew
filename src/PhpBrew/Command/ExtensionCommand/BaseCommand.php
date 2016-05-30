<?php

namespace PhpBrew\Command\ExtensionCommand;

use PhpBrew\Extension;

abstract class BaseCommand extends \CLIFramework\Command
{
    public function prepare()
    {
        parent::prepare();
        if (!getenv('PHPBREW_PHP')) {
            $this->logger->error('Error: PHPBREW_PHP environment variable is not defined.');
            $this->logger->error('  This extension command requires you specify a PHP version from your build list.');
            $this->logger->error("  And it looks like you haven't switched to a version from the builds that were built with PHPBrew.");
            $this->logger->error('Suggestion: Please install at least one PHP with your prefered version and switch to it.');

            return false;
        }

        return true;
    }
}

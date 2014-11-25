<?php
namespace PhpBrew\Command\ExtensionCommand;
use PhpBrew\Config;
use PhpBrew\Extension;
use PhpBrew\Extension\ExtensionManager;
use PhpBrew\Extension\ExtensionFactory;
use PhpBrew\Extension\PeclExtensionInstaller;
use PhpBrew\Utils;
use GetOptionKit\OptionResult;

abstract class BaseCommand extends \CLIFramework\Command
{

    public function prepare() {
        parent::prepare();
        if (!getenv('PHPBREW_PHP')) {
            $this->logger->error("Error: PHPBREW_PHP environment variable is not defined.");
            $this->logger->error("  This extension command requires you specify a PHP version from your build list.");
            $this->logger->error("  And it looks like you have't switched to a version from the builds that were built with PHPBrew.");
            $this->logger->error("Suggestion: Please install at least one PHP with your prefered version and switch to it.");
            return false;
        }

        $buildDir = Config::getCurrentBuildDir();
        $extDir = $buildDir . DIRECTORY_SEPARATOR . 'ext';
        if (!file_exists($extDir)) {
            $this->logger->error("Error: The ext directory '$extDir' does not exist.");
            $this->logger->error("It looks like you don't have the PHP source in $buildDir or you didn't extract the tarball.");
            $this->logger->error("Suggestion: Please install at least one PHP with your prefered version and switch to it.");
            return false;
        }
        return true;
    }

}


<?php
namespace PhpBrew\Extension;
use PEARX;
use CLIFramework\Logger;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use PhpBrew\DirectorySwitch;
use PhpBrew\Config;
use PhpBrew\Utils;
use GetOptionKit\OptionCollection;
use Exception;

class ExtensionInstaller
{
    public $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function install(Extension $ext, array $configureOptions = array()) {
        $sourceDir = $ext->getSourceDirectory();
        $pwd = getcwd();

        chdir($sourceDir);

        $phpizeCommand = 'phpize';
        $phpizeForVersion = Config::getCurrentPhpDir()
            .DIRECTORY_SEPARATOR.'bin'
            .DIRECTORY_SEPARATOR.$phpizeCommand;

        if (file_exists($phpizeForVersion)) {
            $phpizeCommand = $phpizeForVersion;
        }

        Utils::system($phpizeCommand.' > build.log');

        // here we don't want to use closure, because
        // 5.2 does not support closure. We haven't decided whether to
        // support 5.2 yet.
        $escapeOptions = array_map('escapeshellarg', $configureOptions);

        $this->logger->info("===> Configuring...");

        $phpConfig = $phpizeForVersion = Config::getCurrentPhpConfigBin();

        if (file_exists($phpConfig)) {
            $escapeOptions[] = '--with-php-config='.$phpConfig;
        }

        // Utils::system('./configure ' . join(' ', $escapeOptions) . ' >> build.log 2>&1');
        $cmd = './configure ' . join(' ', $escapeOptions);
        if (!$this->logger->isDebug()) {
            $cmd .= ' >> build.log 2>&1';
        }
        $this->logger->debug("Running Command:" . $cmd);
        Utils::system($cmd);


        $this->logger->info("===> Building...");
        $cmd = 'make';
        if (!$this->logger->isDebug()) {
            $cmd .= ' >> build.log 2>&1';
        }
        $this->logger->debug("Running Command:" . $cmd);
        $ret = Utils::system($cmd);

        $this->logger->info("===> Installing...");

        // TODO: use Make task
        // This function is disabled when PHP is running in safe mode.
        passthru('make install');

        // TODO: use getSharedLibraryPath()
        $installedPath = $ext->getSharedLibraryPath();
        $this->logger->debug("Installed extension: " . $installedPath);

        // Try to find the installed path by pattern
        // Installing shared extensions: /Users/c9s/.phpbrew/php/php-5.4.10/lib/php/extensions/debug-non-zts-20100525/
        chdir($pwd);
        $this->logger->info("===> Extension is installed.");
    }
}

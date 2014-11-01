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
use GetOptionKit\OptionResult;
use Exception;

class ExtensionInstaller
{
    public $logger;

    public $options;

    public function __construct(Logger $logger, OptionResult $options = NULL)
    {
        $this->logger = $logger;
        $this->options = $options ?: new \GetOptionKit\OptionResult;
    }

    public function install(Extension $ext, array $configureOptions = array()) {
        $sourceDir = $ext->getSourceDirectory();
        $pwd = getcwd();

        $buildLogPath = $sourceDir . DIRECTORY_SEPARATOR . 'build.log';

        $this->logger->info("Log stored at: $buildLogPath");

        chdir($sourceDir);

        if ($ext->getConfigM4File() !== "config.m4" && ! file_exists($sourceDir . DIRECTORY_SEPARATOR . 'config.m4') ) {
            symlink( $ext->getConfigM4File(), 'config.m4');
        }


        // If the php version is specified, we should get phpize with the correct version.
        $this->logger->info('===> Phpize...');
        Utils::system("phpize > $buildLogPath 2>&1", $this->logger);

        // here we don't want to use closure, because
        // 5.2 does not support closure. We haven't decided whether to
        // support 5.2 yet.
        $escapeOptions = array_map('escapeshellarg', $configureOptions);

        $this->logger->info("===> Configuring...");

        $phpConfig = Config::getCurrentPhpConfigBin();
        if (file_exists($phpConfig)) {
            $this->logger->debug("Appending argument: --with-php-config=$phpConfig");
            $escapeOptions[] = '--with-php-config='.$phpConfig;
        }

        // Utils::system('./configure ' . join(' ', $escapeOptions) . ' >> build.log 2>&1');
        $cmd = './configure ' . join(' ', $escapeOptions);
        if (!$this->logger->isDebug()) {
            $cmd .= " >> $buildLogPath 2>&1";
        }
        Utils::system($cmd, $this->logger);

        $this->logger->info("===> Building...");
        $cmd = array("make", "-C", $sourceDir);
        if (!$this->logger->isDebug()) {
            $cmd[] = " >> $buildLogPath 2>&1";
        }
        $ret = Utils::system($cmd, $this->logger);

        $this->logger->info("===> Installing...");

        // TODO: use Make task
        // This function is disabled when PHP is running in safe mode.
        if ($this->logger->isDebug()) {
            passthru('make install');
        } else {
            Utils::system('make install', $this->logger);
        }

        // TODO: use getSharedLibraryPath()
        $this->logger->debug("Installed extension library: " . $ext->getSharedLibraryPath());

        // Try to find the installed path by pattern
        // Installing shared extensions: /Users/c9s/.phpbrew/php/php-5.4.10/lib/php/extensions/debug-non-zts-20100525/
        chdir($pwd);
        $this->logger->info("===> Extension is installed.");
    }
}

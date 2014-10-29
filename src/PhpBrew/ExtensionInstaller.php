<?php
namespace PhpBrew;
use PEARX;
use CLIFramework\Logger;

class ExtensionInstaller
{
    public $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }


    public function runInstall($packageName, $dir, $configureOptions)
    {
        $this->logger->info("===> Phpizing...");

        $directoryIterator = new \RecursiveDirectoryIterator($dir);
        $it = new \RecursiveIteratorIterator($directoryIterator);

        $extDir = array();
        // search for config.m4 or config0.m4 and use them to determine
        // the directory of the extension's source, because it's not always
        // the root directory in the ext archive (example xhprof)
        foreach ($it as $file) {
            if (basename($file) == 'config.m4') {
                $extDir['config.m4'] = dirname(realpath($file));
                break;
            }

            if (basename($file) == 'config0.m4') {
                $extDir['config0.m4'] = dirname(realpath($file));
            }
        }

        if (isset($extDir['config.m4'])) {

            $sw = new DirectorySwitch;
            $sw->cd($extDir['config.m4']);

        } elseif (isset($extDir['config0.m4'])) {

            $this->logger->warn("File config.m4 not found");
            $this->logger->info("Found config.0.m4, copying to config.m4");

            $sw = new DirectorySwitch;
            $sw->cd($extDir['config0.m4']);

            if (false === copy('config0.m4', 'config.m4')) {
                throw new \Exception("Copy failed.");
            }

        } else {
            throw new \Exception('Neither config.m4 nor config0.m4 was found');
        }

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
        $escapeOptions = array();

        foreach ($configureOptions as $opt) {
            $escapeOptions[] = escapeshellarg($opt);
        }

        $this->logger->info("===> Configuring...");

        $phpConfig = $phpizeForVersion = Config::getCurrentPhpDir()
            .DIRECTORY_SEPARATOR.'bin'
            .DIRECTORY_SEPARATOR.'php-config';

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

        // This function is disabled when PHP is running in safe mode.
        $output = shell_exec('make install');

        if (!$output) {
            throw new \Exception("Extension Install Failed.");
        }

        $this->logger->debug($output);

        $installedPath = null;

        if (preg_match('#Installing shared extensions:\s+(\S+)#', $output, $regs)) {
            $installedPath = $regs[1];
        }

        $installedPath .= strtolower($packageName) . '.so';
        $this->logger->debug("Installed extension: " . $installedPath);

        // Try to find the installed path by pattern
        // Installing shared extensions: /Users/c9s/.phpbrew/php/php-5.4.10/lib/php/extensions/debug-non-zts-20100525/
        $sw->back();
        $this->logger->info("===> Extension is installed.");
        return $dir . '/package.xml';
    }
}

<?php
namespace PhpBrew;
use PEARX;

class ExtensionInstaller
{

    public $pecl = 'pecl.php.net';

    public $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function findPeclPackageUrl($packageName, $version = 'stable')
    {
        $channel = new PEARX\Channel($this->pecl);
        $xml = $channel->fetchPackageReleaseXml($packageName, $version);

        $g = $xml->getElementsByTagName('g');
        $url = $g->item(0)->nodeValue;
        // just use tgz format file.
        return $url . '.tgz';
    }

    /**
     * When running this method, we're current in the directory of {build dir}/{version}/ext
     *
     * TODO: should not depends on chdir()
     * TODO: download the file in distfiles dir.
     */
    public function installFromPecl($packageName, $version = 'stable', $configureOptions = array())
    {
        $url = $this->findPeclPackageUrl($packageName, $version);
        
        $downloader = new Downloader\UrlDownloader($this->logger);
        $basename = $downloader->resolveDownloadFileName($url);

        $distDir = Config::getDistFilesDir();
        $targetFilePath = $distDir . DIRECTORY_SEPARATOR . $basename;

        $downloader->download($url, $targetFilePath);

        $info = pathinfo($basename);
        $extensionExtractedDir = getcwd() . "/$packageName";

        // extract
        $this->logger->info("===> Extracting $basename...");
        Utils::system("tar xf $targetFilePath");
        Utils::system("rm -rf $packageName");
        Utils::system("mv {$info['filename']} $packageName");
        Utils::system("mv package.xml $packageName");
        return $this->runInstall($packageName, $extensionExtractedDir, $configureOptions);
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

        Utils::system('phpize > build.log');

        // here we don't want to use closure, because
        // 5.2 does not support closure. We haven't decided whether to
        // support 5.2 yet.
        $escapeOptions = array();

        foreach ($configureOptions as $opt) {
            $escapeOptions[] = escapeshellarg($opt);
        }

        $this->logger->info("===> Configuring...");

        Utils::system('./configure ' . join(' ', $escapeOptions) . ' >> build.log')
            !== false or die('Configure failed.');

        $this->logger->info("===> Building...");
        Utils::system('make >> build.log');

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

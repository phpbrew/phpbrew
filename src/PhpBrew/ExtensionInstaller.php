<?php
namespace PhpBrew;
use PEARX;
use PhpBrew\Utils;

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


    public function installFromPecl($packageName, $version = 'stable', $configureOptions = array() )
    {
        $url = $this->findPeclPackageUrl($packageName, $version);
        $downloader = new Downloader\UrlDownloader($this->logger);
        $basename = $downloader->download($url);
        $info = pathinfo($basename);
        $extension_dir = $info['filename'];
        // extract
        $this->logger->info("===> Extracting $basename...");
        Utils::system("tar xf $basename");
        Utils::system("mv package.xml $extension_dir    ");

        return $this->runInstall($packageName, $extension_dir, $configureOptions);
    }

    public function runInstall($packageName, $dir, $configureOptions)
    {
        $sw = new DirectorySwitch;
        $sw->cd( $dir );

        $this->logger->info("===> Phpizing...");

        if ( ! file_exists('config.m4') ) {
            $this->logger->warn("File config.m4 not found, checking config0.m4");
            if ( file_exists('config0.m4') ) {
                $this->logger->info("Found config.0.m4, copying to config.m4");
                if ( false === copy('config0.m4','config.m4') ) {
                    throw new Exception("Copy failed.");
                }
            }
        }

        Utils::system('phpize > build.log');

        // here we don't want to use closure, because
        // 5.2 does not support closure. We haven't decided whether to
        // support 5.2 yet.
        $escapeOptions = array();
        foreach( $configureOptions as $opt ) {
            $escapeOptions[] = escapeshellarg($opt);
        }
        $this->logger->info("===> Configuring...");
        Utils::system('./configure ' . join(' ', $escapeOptions) . ' >> build.log' )
            !== false or die('Configure failed.');

        $this->logger->info("===> Building...");
        Utils::system('make >> build.log');

        $this->logger->info("===> Installing...");

        // This function is disabled when PHP is running in safe mode.
        $output = shell_exec('make install');

        if ( ! $output ) {
            throw new Exception("Extension Install Failed.");
        }


        $this->logger->debug($output);

        $installedPath = null;
        if( preg_match('#Installing shared extensions:\s+(\S+)#', $output, $regs) ) {
            $installedPath = $regs[1];
        }

        $installedPath .= strtolower($packageName) . '.so';
        $this->logger->debug("Installed extension: " . $installedPath);


        // Try to find the installed path by pattern
        // Installing shared extensions:     /Users/c9s/.phpbrew/php/php-5.4.10/lib/php/extensions/debug-non-zts-20100525/
        $sw->back();

        $this->logger->info("===> Extension is installed.");
        return $dir . '/package.xml';
    }

}

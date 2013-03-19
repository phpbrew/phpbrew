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

        // extract
        $this->logger->info("===> Extracting $basename...");
        Utils::system("tar xf $basename");

        $info = pathinfo($basename);
        $dir = $info['filename'];

        return $this->runInstall($packageName, $dir, $configureOptions);
    }

    public function runInstall($packageName, $dir, $configureOptions)
    {
        $sw = new DirectorySwitch;
        $sw->cd( $dir );

        $this->logger->info("===> Phpizing...");
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
        $lines = explode("\n", $output );

        $installedPath = null;
        foreach( $lines as $line ) {
            if( preg_match('#Installing shared extensions:\s+(\S+)#',$line, $regs) ) {
                $installedPath = $regs[1];
                break;
            }
        }


        $installedPath .= strtolower($packageName) . '.so';
        $this->logger->debug("Installed extension: " . $installedPath);


        // Try to find the installed path by pattern
        // Installing shared extensions:     /Users/c9s/.phpbrew/php/php-5.4.10/lib/php/extensions/debug-non-zts-20100525/
        $sw->back();

        $this->logger->info("===> Extension is installed.");
        return $installedPath;
    }

}


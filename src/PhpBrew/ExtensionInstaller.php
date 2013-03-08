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


    public function install($packageName, $version = 'stable', $configureOptions = array() )
    {
        $url = $this->findPeclPackageUrl($packageName, $version);
        $downloader = new Downloader\UrlDownloader($this->logger);
        $basename = $downloader->download($url);

        // extract
        $this->logger->info("Extracting $basename...");
        system("tar xf $basename");

        $info = pathinfo($basename);
        $dir = $info['filename'];

        $sw = new DirectorySwitch;
        $sw->cd( $dir );

        $this->logger->info("Phpizing...");
        system('phpize');

        // here we don't want to use closure, because
        // 5.2 does not support closure. We haven't decided whether to
        // support 5.2 yet.
        $escapeOptions = array();
        foreach( $configureOptions as $opt ) {
            $escapeOptions[] = escapeshellarg($opt);
        }
        $this->logger->info("===> Configuring...");
        system('./configure ' . join(' ', $escapeOptions) . ' > /dev/null' )
            !== false or die('Configure failed.');

        $this->logger->info("===> Building...");
        system('make > /dev/null') !== false or die('Build failed.');

        $this->logger->info("===> Installing...");

        // This function is disabled when PHP is running in safe mode.
        $output = shell_exec('make install');
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
        return $installedPath;
    }

}



#    - curl -o APC-3.1.13.tgz http://pecl.php.net/get/APC-3.1.13.tgz
#    - tar -xzf APC-3.1.13.tgz
#    - sh -c "cd APC-3.1.13 && phpize
#    - ./configure && make && sudo make install && cd .."

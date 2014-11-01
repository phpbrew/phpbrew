<?php
namespace PhpBrew\Extension;
use PhpBrew\Extension\Extension;
use PhpBrew\Extension\ExtensionInstaller;
use PhpBrew\Config;
use PhpBrew\Downloader;
use PhpBrew\Utils;
use PEARX;
use PEARX\Channel as PeclChannel;
use CLIFramework\Logger;

class PeclExtensionInstaller extends ExtensionInstaller
{
    /**
     * When running this method, we're current in the directory of {build dir}/{version}/ext
     *
     * TODO: should not depends on chdir()
     * TODO: download the file in distfiles dir.
     */
    public function install($packageName, $version = 'stable', $configureOptions = array())
    {
        $peclDownloader = new PeclExtensionDownloader($this->logger);
        $extensionExtractedDir = $peclDownloader->download($packageName, $version);
        return $this->runInstall($packageName, $extensionExtractedDir, $configureOptions);
    }
}



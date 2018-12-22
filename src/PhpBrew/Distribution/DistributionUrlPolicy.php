<?php

namespace PhpBrew\Distribution;

use PhpBrew\Version;

class DistributionUrlPolicy
{
    private $mirrorSite;

    public function setMirrorSite($mirrorSite)
    {
        $this->mirrorSite = $mirrorSite;
    }

    /**
     * Returns the distribution url for the version.
     */
    public function buildUrl($version, $filename, $museum = false)
    {
        //the historic releases only available at museum
        if ($museum || $this->isDistributedAtMuseum($version)) {
            return 'https://museum.php.net/php5/'.$filename;
        }

        if (!is_null($this->mirrorSite)) {
            // http://tw1.php.net/distributions/php-5.3.29.tar.bz2
            return $this->mirrorSite.'/distributions/'.$filename;
        }

        // http://tw1.php.net/distributions/php-5.3.29.tar.bz2.
        return 'https://secure.php.net/get/'.$filename.'/from/this/mirror';
    }

    private function isDistributedAtMuseum($version)
    {
        $version = new Version($version);

        if ($version->getMajorVersion() > 5) {
            return false;
        }

        if ($version->getMinorVersion() > 4) {
            return false;
        }

        if ($version->getMinorVersion() === 4) {
            return $version->getPatchVersion() <= 21;
        }

        return true;
    }
}

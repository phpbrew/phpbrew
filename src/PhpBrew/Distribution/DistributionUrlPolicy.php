<?php

namespace PhpBrew\Distribution;

use PhpBrew\Version;

class DistributionUrlPolicy
{

    /**
     * Returns the distribution url for the version.
     */
    public function buildUrl($version, $filename, $museum = false)
    {
        //the historic releases only available at museum
        if ($museum || $this->isDistributedAtMuseum($version)) {
            return 'https://museum.php.net/php5/' . $filename;
        }

        return 'https://www.php.net/distributions/' . $filename;
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

<?php
namespace PhpBrew;
use InvalidArgumentException;

/**
 * Version class to handle version conversions:
 *
 *   - 5.3 => php-5.3.29 (get the latest patched version)
 *   - 5.3.29 => php-5.3.29
 *   - php-5.4 => php-5.4.26
 *   - hhvm-3.3 => hhvm-3.3
 *
 * TODO:
 *
 *    - Parse stability
 *
 */
class Version
{
    public $version;

    public $dist = 'php'; // can be hhvm

    public function __construct($a, $dist = NULL)
    {
        // XXX: support stability flag.
        if (preg_match('/^(\w+)-(.*?)$/',$a, $regs)) {
            $this->dist = $dist ?: $regs[1];
            $this->version = $regs[2];
        } else {
            $this->version = $a;
            $this->dist = $dist ?: 'php'; // default dist name to PHP
        }
    }

    public function getMinorVersion() {
        if (preg_match('/(\d+)\.(\d+)\.(\d+)/', $this->version, $regs)) {
            return $regs[2];
        }
        throw new InvalidArgumentException('Incorrect version string: ' . $this->version);
    }

    public function getMajorVersion() {
        if (preg_match('/(\d+)\.(\d+)\.(\d+)/', $this->version, $regs)) {
            return $regs[1];
        }
        throw new InvalidArgumentException('Incorrect version string: ' . $this->version);
    }

    public function getVersion() {
        return $this->version;
    }

    public function getDist() {
        return $this->dist;
    }

    public function compare($b) {
        return version_compare($a->getVersion(), $b->getVersion());
    }


    public function upgradePatchVersion(array $availableVersions) {
        $this->version = self::findLatestPatchVersion($this->getVersion(), $availableVersions);
    }

    public static function hasPatchVersion($version) {
        $va = explode('.', $version);
        return count($va) >= 3;
    }

    public static function findLatestPatchVersion($currentVersion, array $versions) {
        // Trim 5.4.29 to 5.4
        $va = explode('.', $currentVersion);
        if (count($va) == 3) {
            list($cMajor, $cMinor, $cPatch) = $va;
        } elseif(count($va) == 2) {
            list($cMajor, $cMinor) = $va;
            $cPatch = 0;
        }
        foreach ($versions as $version) {
            list($major, $minor, $patch) = explode('.', $version);
            if ($major == $cMajor && $minor == $cMinor && $patch > $cPatch) {
                $cPatch = $patch;
            }
        }
        return join('.', array($cMajor, $cMinor, $cPatch));
    }

    /**
     * @return string the version string, php-5.3.29, php-5.4.2 without prefix
     */
    public function getCanonicalizedVersionName() {
        return $this->dist . '-' . $this->version;
    }

    public function __toString() {
        return $this->getCanonicalizedVersionName();
    }

}





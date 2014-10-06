<?php
namespace PhpBrew;

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
        if (preg_match('/^(\w+)-(.*?)$/',$a, $regs)) {
            $this->dist = $dist ?: $regs[1];
            $this->version = $regs[2];
        } else {
            $this->version = $a;
            $this->dist = $dist ?: 'php'; // default dist name to PHP
        }
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





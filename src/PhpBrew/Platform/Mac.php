<?php
namespace PhpBrew\Platform;

use PhpBrew\Utils;

class Mac implements Platform
{
    private $hardware;

    public function __construct(Hardware $hardware)
    {
        $this->hardware = $hardware;
    }

    public function getOSVersion()
    {
        return Utils::system('sw_vers -productVersion');
    }

    public function isMac()
    {
        return true;
    }

    public function isLinux()
    {
        return false;
    }

    public function isDebian()
    {
        return false;
    }

    public function isCentOS()
    {
        return false;
    }

    public function is32bit()
    {
        return $this->hardware->is32bit();
    }

    public function is64bit()
    {
        return $this->hardware->is64bit();
    }
}

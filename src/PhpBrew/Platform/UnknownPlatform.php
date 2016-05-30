<?php

namespace PhpBrew\Platform;

class UnknownPlatform implements Platform
{
    public function getOSVersion()
    {
        return '';
    }

    public function isMac()
    {
        return false;
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
        return false;
    }

    public function is64bit()
    {
        return false;
    }
}

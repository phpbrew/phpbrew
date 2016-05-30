<?php

namespace PhpBrew\Platform;

interface Platform
{
    public function getOSVersion();
    public function isMac();
    public function isLinux();
    public function isDebian();
    public function isCentOS();
    public function is32bit();
    public function is64bit();
}

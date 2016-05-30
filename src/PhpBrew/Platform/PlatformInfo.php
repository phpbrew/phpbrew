<?php

namespace PhpBrew\Platform;

class PlatformInfo
{
    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            $kernelName = php_uname('s');
            self::$instance = new static(self::createPlatform($kernelName));
        }

        return self::$instance;
    }

    public function isMac()
    {
        return $this->platform->isMac();
    }

    public function isLinux()
    {
        return $this->platform->isLinux();
    }

    public function isDebian()
    {
        return $this->platform->isDebian();
    }

    public function isCentOS()
    {
        return $this->platform->isCentOS();
    }

    private function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    protected static function createPlatform($kernelName)
    {
        if (self::isWindowsKernel($kernelName)) {
            // TODO
            return new UnknownPlatform();
        } elseif (self::isDarwinKernel($kernelName)) {
            return new Mac(new UnixLikeHardware());
        } elseif (self::isLinuxKernel($kernelName)) {
            return new Linux(new UnixLikeHardware());
        }

        return new UnknownPlatform();
    }

    private static function isWindowsKernel($kernelName)
    {
        return preg_match('/^Windows/', $kernelName) === 1;
    }

    private static function isDarwinKernel($kernelName)
    {
        return preg_match('/^Darwin/', $kernelName) === 1;
    }

    private static function isLinuxKernel($kernelName)
    {
        return preg_match('/^Linux/', $kernelName) === 1;
    }
}

<?php
namespace PhpBrew\Platform;

use PhpBrew\Platform\Linux\Distribution;
use PhpBrew\Platform\Linux\Debian;
use PhpBrew\Platform\Linux\CentOS;
use PhpBrew\Platform\Linux\UnknownDistribution;

class Linux implements Platform
{
    private $hardware;
    private $distribution;

    public function __construct(Hardware $hardware)
    {
        $this->hardware = $hardware;
        $distribution = new UnknownDistribution();

        if (file_exists('/etc/debian_version')) {
            $distribution = new Debian();
        } elseif (file_exists('/etc/redhat-release')) {
            $distribution = new CentOS();
        }

        $this->setDistribution($distribution);
    }

    public function getOSVersion()
    {
        return $this->distribution->getVersion();
    }

    public function isMac()
    {
        return false;
    }

    public function isLinux()
    {
        return true;
    }

    public function isDebian()
    {
        return $this->distribution->isDebian();
    }

    public function isCentOS()
    {
        return $this->distribution->isCentOS();
    }

    public function is32bit()
    {
        return $this->hardware->is32bit();
    }

    public function is64bit()
    {
        return $this->hardware->is64bit();
    }

    protected function setDistribution($distribution)
    {
        $this->distribution = $distribution;
    }
}

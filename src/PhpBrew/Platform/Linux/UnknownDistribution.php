<?php
namespace PhpBrew\Platform\Linux;

class UnknownDistribution implements Distribution
{
    /**
     * @return string
     */
    public function getVersion()
    {
        return '';
    }

    public function isDebian()
    {
        return false;
    }

    public function isCentOS()
    {
        return false;
    }
}

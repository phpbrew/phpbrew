<?php
namespace PhpBrew\Platform\Linux;

class CentOS implements Distribution
{
    /**
     * @return string
     */
    public function getVersion()
    {
        return file_get_contents('/etc/redhat-release');
    }

    public function isDebian()
    {
        return false;
    }

    public function isCentOS()
    {
        return true;
    }
}

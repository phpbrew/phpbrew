<?php

namespace PhpBrew\Platform\Linux;

class Debian implements Distribution
{
    /**
     * @return string
     */
    public function getVersion()
    {
        return file_get_contents('/etc/debian_version');
    }

    public function isDebian()
    {
        return true;
    }

    public function isCentOS()
    {
        return false;
    }
}

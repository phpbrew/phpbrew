<?php
namespace PhpBrew\Platform\Linux;

interface Distribution
{
    /**
     * @return string
     */
    public function getVersion();

    /**
     * @return boolean
     */
    public function isDebian();

    /**
     * @return boolean
     */
    public function isCentOS();
}

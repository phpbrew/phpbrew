<?php

namespace PhpBrew\Platform\Linux;

interface Distribution
{
    /**
     * @return string
     */
    public function getVersion();

    /**
     * @return bool
     */
    public function isDebian();

    /**
     * @return bool
     */
    public function isCentOS();
}

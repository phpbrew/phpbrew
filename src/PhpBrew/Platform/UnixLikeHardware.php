<?php
namespace PhpBrew\Platform;

use PhpBrew\Utils;

class UnixLikeHardware implements Hardware
{
    public function is32bit()
    {
        return $this->detectBitness() === 32;
    }

    public function is64bit()
    {
        return $this->detectBitness() === 64;
    }

    protected function detectBitness()
    {
        return intval(Utils::system('getconf LONG_BIT'));
    }
}

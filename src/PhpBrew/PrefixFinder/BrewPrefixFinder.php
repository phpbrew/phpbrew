<?php

namespace PhpBrew\PrefixFinder;

use PhpBrew\Utils;

class BrewPrefixFinder
{
    public function __construct()
    {
        $this->bin = Utils::findBin('brew');
    }

    public function find($formula, &$reason = null)
    {
        if ($prefix = $this->execLine("{$this->bin} --prefix {$formula}")) {
            if (file_exists($prefix)) {
                return $prefix;
            }
            $reason = "$prefix doesn't exist.";

            return false;
        }
        $reason = "hombrew formula {$formula} not found.";

        return false;
    }

    protected function execLine($command)
    {
        $output = array();
        exec($command, $output, $retval);
        if ($retval === 0) {
            $output = array_filter($output);

            return end($output);
        }

        return false;
    }
}

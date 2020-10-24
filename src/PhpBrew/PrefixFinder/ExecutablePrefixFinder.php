<?php

namespace PhpBrew\PrefixFinder;

use PhpBrew\PrefixFinder;
use PhpBrew\Utils;

/**
 * The strategy of finding prefix by an executable file.
 */
final class ExecutablePrefixFinder implements PrefixFinder
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name Executable name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function findPrefix()
    {
        $bin = Utils::findBin('pg_config');

        if ($bin === null) {
            return null;
        }

        return dirname($bin);
    }
}

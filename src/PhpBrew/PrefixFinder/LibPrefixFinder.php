<?php

namespace PhpBrew\PrefixFinder;

use PhpBrew\PrefixFinder;
use PhpBrew\Utils;

/**
 * The strategy of finding prefix using library file path.
 */
final class LibPrefixFinder implements PrefixFinder
{
    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     */
    public function findPrefix()
    {
        return Utils::findLibPrefix($this->path);
    }
}

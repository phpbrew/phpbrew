<?php

namespace PHPBrew\PrefixFinder;

use PHPBrew\PrefixFinder;

/**
 * The strategy of using the user-provided prefix.
 */
final class UserProvidedPrefix implements PrefixFinder
{
    /**
     * @var string|null
     */
    private $prefix;

    /**
     * @param string|null $prefix User-provided prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function findPrefix()
    {
        return $this->prefix;
    }
}

<?php

namespace PhpBrew\Tests;

use PhpBrew\PrefixFinder\ExecutablePrefixFinder;
use PHPUnit\Framework\TestCase;

/**
 * @group prefixfinder
 */
class ExecutablePrefixFinderTest extends TestCase
{
    public function testFindValid()
    {
        $epf = new ExecutablePrefixFinder('ls');
        $this->assertNotNull($epf->findPrefix());
    }

    public function testFindInvalid()
    {
        $epf = new ExecutablePrefixFinder('inexistent-binary');
        $this->assertNull($epf->findPrefix());
    }
}

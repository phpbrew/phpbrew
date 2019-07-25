<?php

namespace PhpBrew\PrefixFinder;

use PhpBrew\PrefixFinder;
use PhpBrew\Utils;

/**
 * The strategy of finding prefix using Homebrew.
 */
final class BrewPrefixFinder implements PrefixFinder
{
    /**
     * @var string
     */
    private $formula;

    /**
     * @param string $formula Homebrew formula
     */
    public function __construct($formula)
    {
        $this->formula = $formula;
    }

    /**
     * {@inheritDoc}
     */
    public function findPrefix()
    {
        $brew = Utils::findBin('brew');

        if ($brew === null) {
            return null;
        }

        $output = $this->execLine(
            sprintf('%s --prefix %s', escapeshellcmd($brew), escapeshellarg($this->formula))
        );

        if ($output === null) {
            printf('Homebrew formula "%s" not found.' . PHP_EOL, $this->formula);

            return null;
        }

        if (!file_exists($output)) {
            printf('Homebrew prefix "%s" does not exist.' . PHP_EOL, $output);

            return null;
        }

        return $output;
    }

    private function execLine($command)
    {
        $output = array();
        exec($command, $output, $retval);

        if ($retval === 0) {
            $output = array_filter($output);

            return end($output);
        }

        return null;
    }
}

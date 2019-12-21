<?php

namespace PHPBrew\Console\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
abstract class VirtualCommand extends Command
{
    final public function execute(InputInterface $input, OutputInterface $output)
    {
        throw new RuntimeException(
            "You should not see this. "
            . "If you see this, it means you didn't load the ~/.phpbrew/bashrc script. "
            . "Please check if bashrc is sourced in your shell."
        );
    }
}

<?php

namespace PHPBrew\Console\Command;

final class EachCommand extends VirtualCommand
{
    protected static $defaultName = 'each';

    protected function configure()
    {
        $this
            ->setDescription('Run a given shell command for each PHP build')
        ;
    }
}

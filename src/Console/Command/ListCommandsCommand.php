<?php

namespace PHPBrew\Console\Command;

use Symfony\Component\Console\Command\ListCommand;

final class ListCommandsCommand extends ListCommand
{
    protected static $defaultName = 'list-commands';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('list-commands')
            ->setHidden(true)
        ;
    }
}

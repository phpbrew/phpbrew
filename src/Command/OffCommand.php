<?php

namespace PHPBrew\Command;

/**
 * @codeCoverageIgnore
 */
class OffCommand extends VirtualCommand
{
    public function brief()
    {
        return 'Temporarily go back to the system php';
    }
}

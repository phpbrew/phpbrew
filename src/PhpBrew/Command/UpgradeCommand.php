<?php
namespace PhpBrew\Command;

class UpgradeCommand extends VirtualCommand
{
    public function brief() { return 'upgrade current php to the latest minor version.'; }

    public function usage() { return 'phpbrew upgrade (-k)    Use the -k option to keep currently installed php version instead of purging it.'; }
}



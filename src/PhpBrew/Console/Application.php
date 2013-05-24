<?php

namespace PhpBrew\Console;

use PhpBrew\Console\Command\CleanCommand;
use PhpBrew\Console\Command\ConfigCommand;
use PhpBrew\Console\Command\DownloadCommand;
use PhpBrew\Console\Command\EnvCommand;
use PhpBrew\Console\Command\ExtCommand;
use PhpBrew\Console\Command\InfoCommand;
use PhpBrew\Console\Command\InitCommand;
use PhpBrew\Console\Command\InstallCommand;
use PhpBrew\Console\Command\InstalledCommand;
use PhpBrew\Console\Command\KnownCommand;
use PhpBrew\Console\Command\MigratedCommand;
use PhpBrew\Console\Command\PurgeCommand;
use PhpBrew\Console\Command\RemoveCommand;
use PhpBrew\Console\Command\SelfUpdateCommand;
use PhpBrew\Console\Command\SwitchCommand;
use PhpBrew\Console\Command\UseCommand;
use PhpBrew\Console\Command\VariantsCommand;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        error_reporting(-1);

        parent::__construct('PhpBrew', '2.0.0');

        $this->add(new CleanCommand());
        $this->add(new ConfigCommand());
        $this->add(new DownloadCommand());
        $this->add(new EnvCommand());
        // $this->add(new ExtCommand());
        $this->add(new InfoCommand());
        $this->add(new InitCommand());
        $this->add(new InstallCommand());
        $this->add(new KnownCommand());
        $this->add(new InstalledCommand());
        $this->add(new MigratedCommand());
        $this->add(new PurgeCommand());
        $this->add(new RemoveCommand());
        $this->add(new SelfUpdateCommand());
        $this->add(new SwitchCommand());
        $this->add(new UseCommand());
        $this->add(new VariantsCommand());
    }
}

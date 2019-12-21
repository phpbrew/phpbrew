<?php

declare(strict_types=1);

namespace PHPBrew\Console;

use PHPBrew\Console\Command\ListCommandsCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputOption;

final class Application extends SymfonyApplication
{
    private const LOGO = <<<'EOF'
  ______ _   _ ____________
  | ___ \ | | || ___ \ ___ \
  | |_/ / |_| || |_/ / |_/ /_ __ _____      __
  |  __/|  _  ||  __/| ___ \ '__/ _ \ \ /\ / /
  | |   | | | || |   | |_/ / | |  __/\ V  V /
  \_|   \_| |_/\_|   \____/|_|  \___| \_/\_/


EOF;

    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->setDefaultCommand('list-commands');
    }

    protected function getDefaultCommands()
    {
        return [new HelpCommand(), new ListCommandsCommand()];
    }

    public function getHelp()
    {
        return self::LOGO . $this->getLongVersion();
    }

    public function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(
            new InputOption('--no-progress', null, InputOption::VALUE_NONE, 'Do not display progress bar')
        );

        return $definition;
    }
}

<?php

declare(strict_types=1);

use Jean85\PrettyVersions;
use PHPBrew\Console\Application;
use PHPBrew\Console\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$containerBuilder = new ContainerBuilder();
$containerBuilder->register(Command\ListCommand::class, Command\ListCommand::class)
    ->setPublic(true);
$containerBuilder->register(Command\ListCommandsCommand::class, Command\ListCommandsCommand::class)
    ->setPublic(true);

$containerBuilder->register(CommandLoaderInterface::class, ContainerCommandLoader::class)
    ->setArguments([$containerBuilder, [
        Command\ListCommand::getDefaultName() => Command\ListCommand::class,
        Command\ListCommandsCommand::getDefaultName() => Command\ListCommandsCommand::class,
        Command\SystemCommand::getDefaultName() => Command\SystemCommand::class,
    ]]);

$containerBuilder->register(Application::class, Application::class)
    ->setArguments([
        'PHPBrew',
        PrettyVersions::getRootPackageVersion()->getPrettyVersion(),
    ])
    ->addMethodCall('setCommandLoader', [
        new Reference(CommandLoaderInterface::class),
    ]);

$containerBuilder->compile();

return $containerBuilder;

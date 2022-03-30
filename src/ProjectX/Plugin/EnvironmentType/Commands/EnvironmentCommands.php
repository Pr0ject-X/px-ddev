<?php

declare(strict_types=1);

namespace Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands;

use Pr0jectX\Px\ProjectX\Plugin\PluginCommandTaskBase;
use Pr0jectX\PxDDev\DDev;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Define the environment commands.
 */
class EnvironmentCommands extends PluginCommandTaskBase
{
    /**
     * @hook option env:debug
     */
    public function addDebugOptions(Command $command)
    {
        $command->addOption(
            'xdebug',
            null,
            InputOption::VALUE_REQUIRED,
            'Enable or disable xdebug using (on/off).',
        );
    }

    /**
     * @hook option env:execute
     */
    public function addExecuteOptions(Command $command): void
    {
        $command->addOption(
            'service',
            's',
            InputOption::VALUE_REQUIRED,
            'Set docker service container to use.',
            DDev::DEFAULT_SERVICE
        );
    }

    /**
     * @hook option env:ssh
     */
    public function addSshOptions(Command $command): void
    {
        $command->addOption(
            'service',
            's',
            InputOption::VALUE_REQUIRED,
            'Set docker service container to use.',
            DDev::DEFAULT_SERVICE
        );
    }
}

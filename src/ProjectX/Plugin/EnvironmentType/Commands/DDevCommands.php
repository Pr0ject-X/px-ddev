<?php

declare(strict_types=1);

namespace Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands;

use Droath\RoboDDev\Task\loadTasks as ddevTasks;
use Pr0jectX\Px\ProjectX\Plugin\PluginCommandTaskBase;

/**
 * Define the DDev commands.
 */
class DDevCommands extends PluginCommandTaskBase
{
    use ddevTasks;

    /**
     * Set the DDev debug mode.
     *
     * @param ?string $state
     *   Set the debug state (on, off).
     *
     * @return void
     */
    public function ddevDebug(string $state = null): void
    {
        try {
            $states = ['on', 'off'];

            $state = $state || in_array($state, $states, true)
                ? $state
                : $this->askChoice('Turn debug', $states, 'on');

            $this->taskDDevXdebug()->arg($state)->run();
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}

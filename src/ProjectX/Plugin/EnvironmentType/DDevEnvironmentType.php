<?php

namespace Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType;

use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeBase;
use Droath\RoboDDev\Task\loadTasks as ddevTasks;

/**
 * Define the DDev environment type.
 */
class DDevEnvironmentType extends EnvironmentTypeBase
{
    use ddevTasks;

    /**
     * {@inheritDoc}
     */
    public static function pluginId()
    {
        return 'ddev';
    }

    /**
     * {@inheritDoc}
     */
    public static function pluginLabel()
    {
        return 'DDev';
    }

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->taskDDevConfig()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function start()
    {
        $this->taskDDevStart()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function stop()
    {
        $this->taskDDevStop()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function restart()
    {
        $this->taskDDevRestart()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function destroy()
    {
        $this->taskDDevDelete()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function info()
    {
        $this->taskDDevDescribe()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function ssh()
    {
        $this->taskDDevSsh()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function exec($cmd)
    {
        $this->taskDDevExec()->cmd($cmd)->run();
    }
}

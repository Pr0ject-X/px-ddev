<?php

declare(strict_types=1);

namespace Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType;

use Droath\RoboDDev\Task\loadTasks as ddevTasks;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeBase;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentDatabase;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeInterface;
use Pr0jectX\Px\PxApp;
use Pr0jectX\PxDDev\DDev;
use Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands\DatabaseCommands;
use Robo\Contract\TaskInterface;

/**
 * Define the DDev environment type.
 */
class DDevEnvironmentType extends EnvironmentTypeBase
{
    use ddevTasks;

    /**
     * {@inheritDoc}
     */
    public static function pluginId(): string
    {
        return 'ddev';
    }

    /**
     * {@inheritDoc}
     */
    public static function pluginLabel(): string
    {
        return 'DDev';
    }

    /**
     * {@inheritDoc}
     */
    public function registeredCommands(): array
    {
        return array_merge([
            DatabaseCommands::class,
        ], parent::registeredCommands());
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $opts = []): void
    {
        DDev::printBanner();

        $task = $this->taskDDevConfig()
            ->disableSettingsManagement();

        $phpVersions = PxApp::activePhpVersions();

        $task->phpVersion($this->askChoice(
            'Select PHP version',
            $phpVersions,
            $phpVersions[1]
        ));

        $task->webserverType($this->askChoice(
            'Select the web server type',
            DDev::webServerTypes(),
            DDev::DEFAULT_WEBSERVER_TYPE
        ));

        if ($this->confirm('Enable XDebug in the web container?', true)) {
            $task->xdebugEnabled();
        }

        $task->run();
    }

    /**
     * {@inheritDoc}
     */
    public function launch(array $opts = [])
    {
        $schema = $opts['schema'] ?? 'https';

        if ($hostname = DDev::configValue('name')) {
            $this->taskOpenBrowser("{$schema}://{$hostname}.ddev.site")->run();
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function start(array $opts = [])
    {
        DDev::printBanner();

        $this->taskDDevStart()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function stop(array $opts = [])
    {
        $this->taskDDevStop()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function restart(array $opts = [])
    {
        $this->taskDDevRestart()->run();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(array $opts = [])
    {
        $this->taskDDevDelete()->run();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function info(array $opts = [])
    {
        DDev::printBanner();

        $this->taskDDevDescribe()->run();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function ssh(array $opts = [])
    {
        DDev::printBanner();

        $this->askServices($this->taskDDevSsh())->run();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $cmd)
    {
        DDev::printBanner();

        $this->askServices($this->taskDDevExec())
            ->cmd($cmd)
            ->run();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function envAppRoot(): string
    {
        return '/var/www/html/web';
    }

    /**
     * {@inheritDoc}
     */
    public function envPackages(): array
    {
        return [
            'drush',
            'composer'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function envDatabases(): array {
        return [
            EnvironmentTypeInterface::ENVIRONMENT_DB_PRIMARY => (new EnvironmentDatabase())
                ->setPort(3306)
                ->setType('mysql')
                ->setHost('db')
                ->setUsername('db')
                ->setPassword('db')
                ->setDatabase('db')
        ];
    }

    /**
     * Set the DDev task command service.
     *
     * @param \Robo\Contract\TaskInterface $task
     *   The Robo task instance.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task instance using the selected service.
     */
    protected function askServices(TaskInterface $task): TaskInterface
    {
        $task->service($this->askChoice(
            'Select the service to use',
            DDev::services(),
            DDev::DEFAULT_SERVICE
        ));

        return $task;
    }
}

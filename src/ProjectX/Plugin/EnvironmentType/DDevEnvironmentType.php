<?php

declare(strict_types=1);

namespace Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType;

use Droath\RoboDDev\Task\loadTasks as ddevTasks;
use Pr0jectX\Px\CommonCommandTrait;
use Pr0jectX\Px\ConfigTreeBuilder\ConfigTreeBuilder;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeBase;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentDatabase;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeInterface;
use Pr0jectX\Px\ProjectX\Plugin\PluginConfigurationBuilderInterface;
use Pr0jectX\Px\PxApp;
use Pr0jectX\PxDDev\ConsoleQuestionTrait;
use Pr0jectX\PxDDev\DDev;
use Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands\DatabaseCommands;
use Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands\DDevCommands;
use Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands\EnvironmentCommands;
use Robo\Result;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Define the DDev environment type plugin.
 */
class DDevEnvironmentType extends EnvironmentTypeBase implements PluginConfigurationBuilderInterface
{
    use ddevTasks;
    use CommonCommandTrait;

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
            DDevCommands::class,
            DatabaseCommands::class,
            EnvironmentCommands::class
        ], parent::registeredCommands());
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $opts = []): void
    {
        DDev::printBanner();

        $configs = $this->getConfigurations();

        $task = $this->taskDDevConfig()
            ->docroot($configs['app_root'])
            ->projectTld('test')
            ->projectType($this->getProjectType())
            ->phpVersion($configs['php_version'])
            ->nodejsVersion($configs['node_version'])
            ->webserverType($configs['webserver_type'])
            ->disableSettingsManagement();

        if ($this->confirm('Enable XDebug?', true)) {
            $task->xdebugEnabled();
        }

        $task->run();
    }

    /**
     * {@inheritDoc}
     */
    public function launch(array $opts = []): void
    {
        $schema = $opts['schema'] ?? 'https';
        $domain = DDev::configValue('project_tld') ?? 'ddev.site';

        if ($hostname = DDev::configValue('name')) {
            $this->taskOpenBrowser("$schema://$hostname.$domain")->run();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function start(array $opts = []): void
    {
        DDev::printBanner();

        $this->taskDDevStart()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function stop(array $opts = []): void
    {
        $this->taskDDevStop()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function restart(array $opts = []): void
    {
        $this->taskDDevRestart()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(array $opts = []): void
    {
        $this->taskDDevDelete()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function info(array $opts = []): void
    {
        DDev::printBanner();

        $this->taskDDevDescribe()->run();
    }

    /**
     * {@inheritDoc}
     */
    public function ssh(array $opts = []): void
    {
        DDev::printBanner();

        $service = DDev::resolveDockerService(
            $opts['service'] ?? DDev::DEFAULT_SERVICE
        );

        $this->taskDockerExec($service)
            ->option('-it')
            ->exec('/bin/bash')->run();
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $cmd, array $opts = []): Result
    {
        $service = DDev::resolveDockerService(
            $opts['service'] ?? DDev::DEFAULT_SERVICE
        );

        return $this->taskDockerExec($service)
            ->interactive()
            ->exec($cmd)->run();
    }

    /**
     * {@inheritDoc}
     */
    public function envAppRoot(): string
    {
        $appRoot = DDev::DEFAULT_WEB_ROOT;

        if ($docroot = DDev::configValue('docroot')) {
            $appRoot .= "/$docroot";
        }

        return $appRoot;
    }

    /**
     * {@inheritDoc}
     */
    public function envPackages(): array
    {
        return [
            'nvm',
            'yarn',
            'drush',
            'composer'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function envDatabases(): array
    {
        $service = DDev::resolveDockerService('db');
        $port = $this->getDockerHostPort($service, 3306);

        return [
            EnvironmentTypeInterface::ENVIRONMENT_DB_PRIMARY => (new EnvironmentDatabase())
                ->setPort($port)
                ->setType('mysql')
                ->setHost('db')
                ->setUsername('db')
                ->setPassword('db')
                ->setDatabase('db')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function pluginConfiguration(): ConfigTreeBuilder
    {
        $configs = $this->getConfigurations();
        $configBuilder = $this->configTreeInstance();

        $configBuilder->createNode('app_root')
            ->setValue($this->requiredQuestion(
                new Question(
                    $this->formatQuestionDefault('Input the application root', $configs['app_root']),
                    $configs['app_root']
                ),
                'The application root is required!'
            ))
            ->end();

        $framework = $configs['framework'] ?? [];
        $configBuilder->createNode('framework')
            ->setValue(function () use ($framework) {
                $value = [];
                $frameworks = DDev::frameworks();

                $frameworkOptions = array_combine(
                    array_keys($frameworks),
                    array_column($frameworks, 'label')
                );
                $frameworkTypeDefault = $framework['type'] ?? null;

                $value['type'] = $this->askChoice(
                    'Select the application framework',
                    $frameworkOptions,
                    $frameworkTypeDefault
                );
                $frameworkType = $value['type'];

                if (
                    isset($frameworks[$frameworkType]['versions'])
                    && !empty($frameworks[$frameworkType]['versions'])
                ) {
                    $versionOptions = $frameworks[$frameworkType]['versions'];
                    $versionDefault = $framework['version'] ?? null;
                    $value['version'] = $this->askChoice(
                        'Set the application framework version',
                        $versionOptions,
                        $versionDefault
                    );
                }

                return $value;
            })
            ->end();

        $phpVersions = PxApp::activePhpVersions();
        $phpVersion = $configs['php_version'] ?? $phpVersions[1];
        $configBuilder->createNode('php_version')
            ->setValue($this->requiredQuestion(
                new ChoiceQuestion(
                    $this->formatQuestionDefault('Select the application PHP version', $phpVersion),
                    $phpVersions,
                    $phpVersion
                ),
                'The PHP version is required!'
            ))
            ->end();

        $nodeJsVersion = $configs['node_version'] ?? DDev::DEFAULT_NODE_VERSION;
        $configBuilder->createNode('node_version')
            ->setValue($this->requiredQuestion(
                new ChoiceQuestion(
                    $this->formatQuestionDefault('Select the application NodeJS version', $nodeJsVersion),
                    DDev::nodeVersions(),
                    $nodeJsVersion
                ),
                'The NodeJS version is required!'
            ))
            ->end();

        $webServerType = $configs['webserver_type'] ?? DDev::DEFAULT_WEBSERVER_TYPE;
        $configBuilder->createNode('webserver_type')
            ->setValue($this->requiredQuestion(
                new ChoiceQuestion(
                    $this->formatQuestionDefault('Select the application webserver type', $webServerType),
                    DDev::webServerTypes(),
                    $webServerType
                ),
                'The webserver type is required!'
            ))
            ->end();

        $hostname = $configs['hostname'] ?? basename(PxApp::projectRootPath());
        $configBuilder->createNode('hostname')
            ->setValue($this->requiredQuestion(
                new Question(
                    $this->formatQuestionDefault('Input the application hostname', $hostname),
                    $hostname
                ),
                'The application hostname is required!'
            ))
            ->end();

        $configBuilder->createNode('additional_hostnames')->setValue(function () use ($configs) {
            $value = [];

            if ($this->confirm('Input additional hostnames?')) {
                $index = 0;
                $hostnames = $configs['additional_hostnames'] ?? [];

                $hostnameCount = count($hostnames);
                $defaultHostname = $hostnames[$index] ?? null;

                do {
                    $value[$index] = $this->doAsk($this->requiredQuestion(
                        new Question(
                            $this->formatQuestionDefault('Input the additional hostname', $defaultHostname),
                            $defaultHostname
                        ),
                        'This additional hostname is required!'
                    ));
                    ++$index;
                } while ($index < $hostnameCount || $this->confirm('Add another hostname?'));
            }
            return $value;
        })->end();

        return $configBuilder;
    }

    /**
     * Get the DDev project type based on selected framework.
     *
     * @return string|null
     *   The DDev formatted project type.
     */
    protected function getProjectType(): ?string
    {
        $config = $this->getConfigurations();

        if (!isset($config['framework']['type'])) {
            return null;
        }
        $type = $config['framework']['type'];

        if (isset($config['framework']['version'])) {
            $type .= $config['framework']['version'];
        }

        return $type;
    }

    /**
     * Get docker container host port.
     *
     * @param string $service
     *   The docker service name.
     * @param int $containerPort
     *   The docker container port.
     *
     * @return int|null
     */
    protected function getDockerHostPort(
        string $service,
        int $containerPort
    ): ?int {
        $task = $this->taskExec('docker inspect')
            ->arg($service)
            ->option('-f', "{{(index (index .NetworkSettings.Ports \"$containerPort/tcp\") 0).HostPort}}");

        $result = $this->runSilentCommand($task);

        if (!$result->wasSuccessful()) {
            return null;
        }

        return (int) $result->getMessage();
    }

    /**
     * The config tree builder instance.
     *
     * @return \Pr0jectX\Px\ConfigTreeBuilder\ConfigTreeBuilder
     *   The config tree builder instance.
     */
    protected function configTreeInstance(): ConfigTreeBuilder
    {
        return (new ConfigTreeBuilder())
            ->setQuestionInput($this->input())
            ->setQuestionOutput($this->output());
    }
}

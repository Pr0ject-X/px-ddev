<?php

declare(strict_types=1);

namespace Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands;

use Pr0jectX\Px\Contracts\DatabaseInterface;
use Pr0jectX\Px\Database\Database;
use Pr0jectX\Px\ExecutableBuilder\Commands\MySql;
use Pr0jectX\Px\ExecutableBuilder\Commands\MySqlDump;
use Pr0jectX\Px\ProjectX\Plugin\DatabaseCommandTaskBase;
use Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeInterface;
use Pr0jectX\Px\PxApp;

/**
 * Define the environment database commands.
 */
class DatabaseCommands extends DatabaseCommandTaskBase
{
    /**
     * {@inheritDoc}
     */
    protected function importDatabase(
        string $host,
        string $database,
        string $username,
        string $password,
        string $importFile
    ): void {
        if ($command = $this->application()->find('env:execute')) {
            $sqlFileDecompressed = false;

            $mysqlCommand = (new MySql())
                ->host($host)
                ->user($username)
                ->password($password)
                ->database($database)
                ->build();

            if (
                $this->isGzipped($importFile)
                && $this->_exec("gunzip -dk $importFile")->wasSuccessful()
            ) {
                $sqlFileDecompressed = true;
                $importFile = substr($importFile, 0, strrpos($importFile, '.'));

                if (!file_exists($importFile)) {
                    throw new \RuntimeException(
                        'An error occurred after decompressing the database SQL file!'
                    );
                }
            }
            $result = $this->taskSymfonyCommand($command)
                ->arg('cmd', "$mysqlCommand < $importFile")
                ->run();

            if ($result->wasSuccessful()) {
                if ($sqlFileDecompressed) {
                    $this->_remove($importFile);
                }
                $this->success(
                    'The database was successfully imported!'
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function exportDatabase(
        string $host,
        string $database,
        string $username,
        string $password,
        string $exportFile
    ): void {
        if ($command = $this->application()->find('env:execute')) {
            $mysqlDump = (new MySqlDump())
                ->host($host)
                ->user($username)
                ->password($password)
                ->database($database)
                ->noTablespaces()
                ->build();

            $dbFilename = "{$exportFile}.sql.gz";
            $result = $this->taskSymfonyCommand($command)
                ->arg('cmd', "{$mysqlDump} | gzip -c > {$dbFilename}")->run();

            if ($result->wasSuccessful()) {
                $this->success(
                    'The database was successfully exported!'
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function createDatabase(array $config): ?DatabaseInterface
    {
        if (isset($config['host'], $config['database'], $config['username'], $config['password'])) {
            return (new Database())
                ->setType($config['type'])
                ->setHost($config['host'])
                ->setDatabase($config['database'])
                ->setUsername($config['username'])
                ->setPassword($config['password']);
        }

        $envTypes = [
            EnvironmentTypeInterface::ENVIRONMENT_DB_PRIMARY,
            EnvironmentTypeInterface::ENVIRONMENT_DB_SECONDARY,
        ];

        if (isset($config['env_type']) && in_array($config['env_type'], $envTypes, true)) {
            return $this->environmentInstance()->selectEnvDatabase($config['env_type']);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function createLaunchDatabase(): DatabaseInterface
    {
        return ($this->environmentInstance()->selectEnvDatabase(
            EnvironmentTypeInterface::ENVIRONMENT_DB_PRIMARY,
        ));
    }

    /**
     * The current environment type instance.
     *
     * @return \Pr0jectX\Px\ProjectX\Plugin\EnvironmentType\EnvironmentTypeInterface
     */
    protected function environmentInstance(): EnvironmentTypeInterface
    {
        return PxApp::getEnvironmentInstance();
    }
}

<?php

namespace Pr0jectX\PxDDev\ProjectX\Plugin\EnvironmentType\Commands;

use Pr0jectX\Px\CommandTasksBase;
use Droath\RoboDDev\Task\loadTasks as DDevTasks;

/**
 * Define the DDev database commands.
 */
class DatabaseCommands extends CommandTasksBase
{
    use DDevTasks;

    /**
     * Launch the DDev database in Sequel Pro.
     */
    public function dbLaunch(): void
    {
        $this->taskDDevSequelPro()->run();
    }

    /**
     * Import the database to the DDev environment.
     *
     * @param string $importFile
     *   The database import file.
     */
    public function dbImport(string $importFile): void
    {
        if (!file_exists($importFile)) {
            throw new \InvalidArgumentException(
                'The source database file does not exist.'
            );
        }

        $this->taskDDevImportDb()
            ->progress()
            ->src($importFile)
            ->run();
    }

    /**
     * Export the database from the DDev environment.
     *
     * @param string $export_dir
     *   The local export directory.
     * @param array $opts
     * @option $filename
     *   The filename of the database export.
     */
    public function dbExport(string $export_dir, $opts = ['filename' => 'db']): void
    {
        if (!is_dir($export_dir)) {
            throw new \InvalidArgumentException(
                'The export directory does not exist.'
            );
        }

        $this->taskDDevExportDb()
            ->file("{$export_dir}/{$opts['filename']}.sql.gz")
            ->gzip()
            ->run();
    }
}

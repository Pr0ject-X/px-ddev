<?php

declare(strict_types=1);

namespace Pr0jectX\PxDDev;

use Pr0jectX\Px\PxApp;
use Pr0jectX\PxDrupalVM\DrupalVM;
use Pr0jectX\PxDrupalVM\ProjectX\Plugin\EnvironmentType\DrupalVMEnvironmentType;
use Symfony\Component\Yaml\Yaml;

/**
 * Define the DDev plugin reusable configurations.
 */
class DDev
{
    /**
     * @var string
     */
    public const CONFIG_DIR = '.ddev';

    /**
     * @var string
     */
    public const CONFIG_FILE = 'config.yaml';

    /**
     * @var string
     */
    public const DEFAULT_SERVICE = 'web';

    /**
     * @var string
     */
    public const DEFAULT_WEBSERVER_TYPE = 'apache-fpm';

    /**
     * @var array
     */
    protected static $configs = [];

    /**
     * The DDev root path.
     *
     * @return string
     *   The root path of the plugin.
     */
    public static function rootPath() : string
    {
        return dirname(__DIR__);
    }

    /**
     * Print the DDev plugin logo.
     */
    public static function printBanner(): void
    {
        print file_get_contents(
           static::rootPath() . '/banner.txt'
        );
    }

    /**
     * Available DDev web server types.
     *
     * @return array|string[]
     *   An array of web server types.
     */
    public static function webServerTypes(): array
    {
        return [
            'nginx-fpm',
            'apache-fpm',
            'apache-cgi'
        ];
    }

    /**
     * Available DDev container services.
     *
     * @return string[]
     *   An array of docker services.
     */
    public static function services()
    {
        return [
            'db' => 'Database',
            'web' => 'Web'
        ];
    }

    /**
     * Load DDev configs.
     *
     * @return array
     *   An array of DDev configs.
     */
    public static function loadConfigs(): array
    {
        if (static::hasConfigFile()) {
            if (!isset(static::$configs) || empty(static::$configs)) {
                static::$configs = Yaml::parseFile(
                    static::configFilePath()
                );
            }
        }

        return static::$configs;
    }

    /**
     * Retrieve a DDev config value.
     *
     * @param string $name
     *   The DDev configuration property name.
     *
     * @return bool|mixed
     *   The DDev configuration value; otherwise FALSE.
     */
    public static function configValue(string $name)
    {
        return static::loadConfigs()[$name] ?? FALSE;
    }

    /**
     * Has DDev configuration been set.
     *
     * @return bool
     *   Return true if configuration exist; otherwise false.
     */
    public static function hasConfigFile(): bool
    {
        return file_exists(static::configFilePath());
    }

    /**
     * DDev configuration file path.
     *
     * @return string
     *   The configuration file path.
     */
    public static function configFilePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            PxApp::projectRootPath(),
            static::CONFIG_DIR,
            static::CONFIG_FILE
        ]);
    }
}

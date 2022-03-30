<?php

declare(strict_types=1);

namespace Pr0jectX\PxDDev;

use Pr0jectX\Px\PxApp;
use Pr0jectX\PxDrupalVM\DrupalVM;
use Pr0jectX\PxDrupalVM\ProjectX\Plugin\EnvironmentType\DrupalVMEnvironmentType;
use Symfony\Component\Yaml\Yaml;

/**
 * Define the DDev static instance.
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
    public const GLOBAL_CONFIG_FILE = 'global_config.yaml';

    /**
     * @var string
     */
    public const DEFAULT_SERVICE = 'web';

    /**
     * @var string
     */
    public const DEFAULT_NODE_VERSION = 16;

    /**
     * @var string
     */
    public const DEFAULT_WEB_ROOT = '/var/www/html';

    /**
     * @var string
     */
    public const DEFAULT_WEBSERVER_TYPE = 'nginx-fpm';

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
    public static function rootPath(): string
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
    public static function services(): array
    {
        return [
            'db' => 'Database',
            'web' => 'Web'
        ];
    }

    /**
     * Available DDev node versions.
     *
     * @return int[]
     *   An array of the supported node versions.
     */
    public static function nodeVersions(): array
    {
        return [12, 14, 16, 17];
    }

    /**
     * Available DDev frameworks.
     *
     * @return array
     *   An array of supported frameworks.
     */
    public static function frameworks(): array
    {
        return [
            'drupal' => [
                'label' => 'Drupal',
                'versions' => [6, 7, 8, 9, 10]
            ],
            'magento' => [
                'label' => 'Magento',
                'versions' => [1, 2]
            ],
            'backdrop' => [
                'label' => 'Backdrop'
            ],
            'php' => ['label' => 'PHP'],
            'typo3' => ['label' => 'Typo3'],
            'laravel' => ['label' => 'Laravel'],
            'shopware6' => ['label' => 'Shopware'],
            'wordpress' => ['label' => 'WordPress'],
        ];
    }

    /**
     * Resolve the DDEV docker service.
     *
     * @param string $service
     *   The docker service name.
     *
     * @return string|null
     *   The docker service name.
     */
    public static function resolveDockerService(
        string $service
    ): ?string {
        $dockerService = null;

        if ($name = static::configValue('name')) {
            $dockerService = "ddev-$name-$service";
        }

        return $dockerService;
    }

    /**
     * Retrieve a DDev config value.
     *
     * @param string $name
     *   The DDev configuration property name.
     *
     * @return null|mixed
     *   The DDev configuration value; otherwise null.
     */
    public static function configValue(string $name, $global = false)
    {
        return static::loadConfigs($global)[$name] ?? null;
    }

    /**
     * Load DDev configs.
     *
     * @return array
     *   An array of DDev configs.
     */
    protected static function loadConfigs($global = false): array
    {
        $type = $global ? 'global' : 'project';

        if (!isset(static::$configs[$type]) || empty(static::$configs[$type])) {
            if ($path = static::configFilePath($global)) {
                static::$configs[$type] = Yaml::parseFile($path);
            }
        }

        return static::$configs[$type];
    }

    /**
     * DDev configuration file path.
     *
     * @return string
     *   The configuration file path.
     */
    protected static function configFilePath($global = false): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $global ? PxApp::userDir() : PxApp::projectRootPath(),
            static::CONFIG_DIR,
            $global ? static::GLOBAL_CONFIG_FILE : static::CONFIG_FILE
        ]);
    }
}

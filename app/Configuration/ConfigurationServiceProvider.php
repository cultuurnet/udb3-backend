<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Configuration;

use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use League\Container\Argument\Literal\StringArgument;

final class ConfigurationServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ApiName::class,
            'system_user_id',
            'debug',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'config',
            fn () => $this->getConfiguration($container)
        );

        if (!defined('API_NAME')) {
            define('API_NAME', ApiName::UNKNOWN);
        }
        $container->addShared(ApiName::class, new StringArgument(API_NAME));

        $container->addShared(
            'system_user_id',
            fn () => '00000000-0000-0000-0000-000000000000',
        );

        $container->addShared(
            'debug',
            fn () => $container->get('config')['debug'] ?? false
        );
    }

    private function getConfiguration($container): array
    {
        $config = file_exists(__DIR__ . '/../../config.php') ? require __DIR__ . '/../../config.php' : [];

        // Add the system user to the list of god users.
        return array_merge_recursive(
            $config,
            [
                'user_permissions' => [
                    'allow_all' => [
                        $container->get('system_user_id'),
                    ],
                ],
            ]
        );
    }
}

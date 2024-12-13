<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Configuration;

use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use League\Container\Argument\Literal\StringArgument;
use League\Container\DefinitionContainerInterface;

final class ConfigurationServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ApiName::class,
            'system_user_id',
            'debug',
            'config',
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
            fn () => Uuid::NIL,
        );

        $container->addShared(
            'debug',
            fn () => $container->get('config')['debug'] ?? false
        );
    }

    private function getConfiguration(DefinitionContainerInterface $container): array
    {
        $config = file_exists(__DIR__ . '/../../config.php') ? require __DIR__ . '/../../config.php' : [];

        $clientPermissions = [];
        foreach ($config['client_permissions'] ?? [] as $clientId => $clientPermissionsConfig) {
            // Add @clients suffix to client id if missing in the config
            // @todo change to str_ends_with() in PHP 8.x
            if (strpos($clientId, '@clients') === false) {
                $clientId .= '@clients';
            }

            // Convert all permissions for the client id to Permission objects
            $clientPermissionsConfig['permissions'] = array_map(
                fn (string $permission) => new Permission($permission),
                $clientPermissionsConfig['permissions'] ?? []
            );

            $clientPermissions[$clientId] = $clientPermissionsConfig;
        }
        $config['client_permissions'] = $clientPermissions;

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

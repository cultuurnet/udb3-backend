<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\User\GetCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\User\GetUserByEmailRequestHandler;
use CultuurNet\UDB3\Security\InMemoryUserEmailAddressRepository;
use CultuurNet\UDB3\Security\UserEmailAddressRepository;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use CultuurNet\UDB3\User\Auth0\Auth0ManagementTokenGenerator;
use CultuurNet\UDB3\User\Auth0\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\Keycloak\KeycloakUserIdentityResolver;
use CultuurNet\UDB3\User\Keycloak\KeycloakManagementTokenGenerator;
use CultuurNet\UDB3\User\ManagementToken\CacheRepository;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Client;
use League\Container\DefinitionContainerInterface;

final class UserServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ManagementTokenProvider::class,
            UserIdentityResolver::class,
            'cdbxml_created_by_resolver',
            GetUserByEmailRequestHandler::class,
            GetCurrentUserRequestHandler::class,
            UserEmailAddressRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ManagementTokenProvider::class,
            fn () => $this->getManagementTokenProvider($container)
        );

        $container->addShared(
            UserIdentityResolver::class,
            fn () => $this->getUserIdentityResolver($container)
        );

        $container->addShared(
            'cdbxml_created_by_resolver',
            function () use ($container) {
                $resolver = new CdbXmlCreatedByToUserIdResolver(
                    $container->get(UserIdentityResolver::class)
                );

                $resolver->setLogger(LoggerFactory::create($container, LoggerName::forService('xml-conversion', 'created-by-resolver')));

                return $resolver;
            }
        );

        $container->addShared(
            GetUserByEmailRequestHandler::class,
            fn () => new GetUserByEmailRequestHandler($container->get(UserIdentityResolver::class))
        );

        $container->addShared(
            GetCurrentUserRequestHandler::class,
            fn () => new GetCurrentUserRequestHandler(
                $container->get(UserIdentityResolver::class),
                $container->get(JsonWebToken::class)
            )
        );

        $container->addShared(
            UserEmailAddressRepository::class,
            fn () => new InMemoryUserEmailAddressRepository($container->get(JsonWebToken::class))
        );
    }

    private function getManagementTokenProvider(DefinitionContainerInterface $container): ManagementTokenProvider
    {
        if ($container->get('config')['keycloak']['enabled']) {
            return new ManagementTokenProvider(
                new KeycloakManagementTokenGenerator(
                    new Client(),
                    $container->get('config')['keycloak']['domain'],
                    $container->get('config')['keycloak']['client_id'],
                    $container->get('config')['keycloak']['client_secret']
                ),
                new CacheRepository(
                    $container->get('cache')('keycloak-management-token')
                )
            );
        }

        return new ManagementTokenProvider(
            new Auth0ManagementTokenGenerator(
                new Client(),
                $container->get('config')['auth0']['client_id'],
                $container->get('config')['auth0']['domain'],
                $container->get('config')['auth0']['client_secret']
            ),
            new CacheRepository(
                $container->get('cache')('auth0-management-token')
            )
        );
    }

    private function getUserIdentityResolver(DefinitionContainerInterface $container): UserIdentityResolver
    {
        if ($container->get('config')['keycloak']['enabled']) {
            return $container->get(KeycloakUserIdentityResolver::class);
        }

        return $container->get(Auth0UserIdentityResolver::class);
    }
}

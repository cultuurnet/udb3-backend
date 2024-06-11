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
use CultuurNet\UDB3\User\Auth0\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\Keycloack\KeycloackUserIdentityResolver;
use League\Container\Container;

final class UserServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
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

    private function getUserIdentityResolver(Container $container): UserIdentityResolver
    {
        if ($container->get('config')['keycloack']['enabled']) {
            return $container->get(KeycloackUserIdentityResolver::class);
        }

        return $container->get(Auth0UserIdentityResolver::class);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\User\GetCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\User\GetUserByEmailRequestHandler;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;

final class UserServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'cdbxml_created_by_resolver',
            GetUserByEmailRequestHandler::class,
            GetCurrentUserRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'cdbxml_created_by_resolver',
            function () use ($container) {
                $resolver = new CdbXmlCreatedByToUserIdResolver(
                    $container->get(Auth0UserIdentityResolver::class)
                );

                $resolver->setLogger(LoggerFactory::create($container, LoggerName::forService('xml-conversion', 'created-by-resolver')));

                return $resolver;
            }
        );

        $container->addShared(
            GetUserByEmailRequestHandler::class,
            fn () => new GetUserByEmailRequestHandler($container->get(Auth0UserIdentityResolver::class))
        );

        $container->addShared(
            GetCurrentUserRequestHandler::class,
            fn () => new GetCurrentUserRequestHandler(
                $container->get(Auth0UserIdentityResolver::class),
                $container->get(JsonWebToken::class)
            )
        );
    }
}

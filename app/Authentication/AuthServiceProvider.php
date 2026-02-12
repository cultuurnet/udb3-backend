<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Authentication;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\CultureFeedConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\Cache\CachedApiKeyAuthenticator;
use CultuurNet\UDB3\Cache\CachedConsumerReadRepository;
use CultuurNet\UDB3\Cache\CacheFactory;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\Auth\Jwt\UitIdV1JwtValidator;
use CultuurNet\UDB3\Http\Auth\Jwt\UitIdV2JwtValidator;
use CultuurNet\UDB3\Http\Auth\RequestAuthenticatorMiddleware;
use CultuurNet\UDB3\Impersonator;
use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\User\ApiKeysMatchedToClientIds;
use CultuurNet\UDB3\User\ClientIdResolver;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\InMemoryApiKeysMatchedToClientIds;
use League\Container\DefinitionContainerInterface;

final class AuthServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RequestAuthenticatorMiddleware::class,
            CurrentUser::class,
            JsonWebToken::class,
            ApiKey::class,
            ConsumerReadRepository::class,
            Consumer::class,
            'impersonator',
            ApiKeysMatchedToClientIds::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        CurrentUser::configureGodUserIds($container->get('config')['user_permissions']['allow_all']);

        $container->addShared(
            RequestAuthenticatorMiddleware::class,
            function () use ($container): RequestAuthenticatorMiddleware {
                $authenticator = new RequestAuthenticatorMiddleware(
                    $this->createUitIdV2JwtValidator($container),
                    new CachedApiKeyAuthenticator(
                        new CultureFeedApiKeyAuthenticator($container->get(ConsumerReadRepository::class)),
                        CacheFactory::create(
                            $container->get('app_cache'),
                            'api_key',
                            86400
                        )
                    ),
                    $container->get(ConsumerReadRepository::class),
                    new ConsumerIsInPermissionGroup((string) $container->get('config')['api_key']['group_id']),
                    $container->get(UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY),
                    $container->get(ClientIdResolver::class),
                    $container->get('config')['match_api_keys_to_client_ids'] ? $container->get(ApiKeysMatchedToClientIds::class) : null
                );

                // We can not expect the ids of events, places and organizers to be correctly formatted as UUIDs,
                // because there is no exhaustive documentation about how this is handled in UDB2. Therefore we take a
                // rather liberal approach and allow all alphanumeric characters and a dash as ids.
                $authenticator->addPublicRoute('~^/offers/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/?$~', ['GET'], 'embedContributors');
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/calendar-summary/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/permissions?/.+$~', ['GET']);
                $authenticator->addPublicRoute('~^/organizers/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/organizers/[\w\-]+/?$~', ['GET'], 'embedContributors');
                $authenticator->addPublicRoute('~^/organizers/[\w\-]+/permissions/.+$~', ['GET']);
                $authenticator->addPublicRoute('~^/labels/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/label/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/media/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/images/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/jobs~', ['GET']);
                $authenticator->addPublicRoute('~^/uitpas~', ['GET']);
                $authenticator->addPublicRoute('~^/news-articles~', ['GET', 'DELETE', 'POST', 'PUT']);
                $authenticator->addPublicRoute('~^/cultuurkuur~', ['GET']);

                // Legacy URLs that get rewritten. Still needed when we're still using the Silex router because in Silex
                // the rewrite happens after the auth check. But can be removed once we use the new PSR router for all
                // routes because on the new router the rewrite will happen before the auth check.
                $authenticator->addPublicRoute('~^/(events|event|places|place)/[\w\-]+/calsum/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/news_articles~', ['GET', 'DELETE', 'POST', 'PUT']);

                // Permission checks on routes that do not dispatch commands (commands already have permission checks
                // built-in). In the future when all controllers are refactored to RequestHandlerInterface
                // implementations we can move this to a RequestHandlerInterface decorator instead so we can put this
                // logic on the RequestHandler itself, instead of having to work with (fragile) URL regexes.
                $authenticator->addPermissionRestrictedRoute('~^/permissions~', ['GET'], Permission::gebruikersBeheren());
                $authenticator->addPermissionRestrictedRoute('~^/roles~', ['GET'], Permission::gebruikersBeheren());
                $authenticator->addPermissionRestrictedRoute('~^/users~', ['GET'], Permission::gebruikersBeheren());

                return $authenticator;
            }
        );

        $container->addShared(
            CurrentUser::class,
            static function () use ($container): CurrentUser {
                // Check first if we're impersonating someone.
                // This is done when handling async commands via a CLI worker.
                /* @var Impersonator $impersonator */
                $impersonator = $container->get('impersonator');
                if ($impersonator->getUserId()) {
                    return new CurrentUser($impersonator->getUserId());
                }

                /* @var RequestAuthenticatorMiddleware $requestAuthenticator */
                $requestAuthenticator = $container->get(RequestAuthenticatorMiddleware::class);
                return $requestAuthenticator->getCurrentUser();
            }
        );

        $container->addShared(
            JsonWebToken::class,
            function () use ($container): ?JsonWebToken {
                // Check first if we're impersonating someone.
                // This is done when handling async commands via a CLI worker.
                /* @var Impersonator $impersonator */
                $impersonator =  $container->get('impersonator');
                if ($impersonator->getJwt()) {
                    return $impersonator->getJwt();
                }

                /* @var RequestAuthenticatorMiddleware $requestAuthenticator */
                $requestAuthenticator = $container->get(RequestAuthenticatorMiddleware::class);
                return $requestAuthenticator->getToken();
            }
        );

        $container->addShared(
            ApiKey::class,
            function () use ($container): ?ApiKey {
                // Check first if we're impersonating someone.
                // This is done when handling async commands via a CLI worker.
                /* @var Impersonator $impersonator */
                $impersonator = $container->get('impersonator');
                if ($impersonator->getApiKey()) {
                    return $impersonator->getApiKey();
                }

                // If not impersonating then use the api key from the request.
                /** @var RequestAuthenticatorMiddleware $requestAuthenticator */
                $requestAuthenticator = $container->get(RequestAuthenticatorMiddleware::class);
                return $requestAuthenticator->getApiKey();
            }
        );

        $container->addShared(
            ConsumerReadRepository::class,
            function () use ($container): ConsumerReadRepository {
                return new CachedConsumerReadRepository(
                    new CultureFeedConsumerReadRepository($container->get('culturefeed'), true),
                    CacheFactory::create(
                        $container->get('app_cache'),
                        'culturefeed_consumer',
                        86400
                    )
                );
            }
        );

        $container->addShared(
            Consumer::class,
            static function () use ($container): ?Consumer {
                $apiKey = $container->get(ApiKey::class);
                if ($apiKey === null) {
                    return null;
                }

                /** @var ConsumerReadRepository $consumerReadRepository */
                $consumerReadRepository = $container->get(ConsumerReadRepository::class);
                return $consumerReadRepository->getConsumer($apiKey);
            }
        );

        // This service is used by the background worker to impersonate the user
        // who initially queued the command.
        $container->addShared(
            'impersonator',
            fn () => new Impersonator()
        );

        $container->addShared(
            ApiKeysMatchedToClientIds::class,
            fn () => new InMemoryApiKeysMatchedToClientIds(
                file_exists(__DIR__ . '/../../config.api_keys_matched_to_client_ids.php') ? require __DIR__ . '/../../config.api_keys_matched_to_client_ids.php' : []
            )
        );
    }

    private function createUitIdV2JwtValidator(DefinitionContainerInterface $container): UitIdV2JwtValidator
    {
        return new UitIdV2JwtValidator(
            'file://' . __DIR__ . '/../../' . $container->get('config')['jwt']['keycloak']['keys']['public']['file'],
            $container->get('config')['jwt']['keycloak']['valid_issuers'],
            $container->get('config')['jwt']['keycloak']['jwt_provider_client_id']
        );
    }
}

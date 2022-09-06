<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Authentication;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\CultureFeedConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\Http\Auth\RequestAuthenticator;
use CultuurNet\UDB3\Http\Auth\Jwt\UitIdV1JwtValidator;
use CultuurNet\UDB3\Http\Auth\Jwt\UitIdV2JwtValidator;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Silex\Impersonator;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        CurrentUser::configureGodUserIds($app['config']['user_permissions']['allow_all']);

        $app[RequestAuthenticator::class] = $app::share(
            function (Application $app): RequestAuthenticator {
                $authenticator = new RequestAuthenticator(
                    new UitIdV1JwtValidator(
                        'file://' . __DIR__ . '/../../' . $app['config']['jwt']['v1']['keys']['public']['file'],
                        $app['config']['jwt']['v1']['valid_issuers']
                    ),
                    new UitIdV2JwtValidator(
                        'file://' . __DIR__ . '/../../' . $app['config']['jwt']['v2']['keys']['public']['file'],
                        $app['config']['jwt']['v2']['valid_issuers'],
                        $app['config']['jwt']['v2']['jwt_provider_client_id']
                    ),
                    new CultureFeedApiKeyAuthenticator($app[ConsumerReadRepository::class]),
                    $app[ConsumerReadRepository::class],
                    new ConsumerIsInPermissionGroup((string) $app['config']['api_key']['group_id']),
                    $app[UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY]
                );

                // We can not expect the ids of events, places and organizers to be correctly formatted as UUIDs,
                // because there is no exhaustive documentation about how this is handled in UDB2. Therefore we take a
                // rather liberal approach and allow all alphanumeric characters and a dash as ids.
                $authenticator->addPublicRoute('~^/(events?|places?)/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/calendar-summary/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/permissions?/.+$~', ['GET']);
                $authenticator->addPublicRoute('~^/organizers/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/organizers/[\w\-]+/permissions/.+$~', ['GET']);
                $authenticator->addPublicRoute('~^/labels/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/label/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/media/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/images/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/jobs~', ['GET']);
                $authenticator->addPublicRoute('~^/uitpas~', ['GET']);
                $authenticator->addPublicRoute('~^/news-articles~', ['GET', 'DELETE', 'POST', 'PUT']);

                // Legacy URLs that get rewritten. Still needed when we're still using the Silex router because in Silex
                // the rewrite happens after the auth check. But can be removed once we use the new PSR router for all
                // routes because on the new router the rewrite will happen before the auth check.
                $authenticator->addPublicRoute('^/(events|event|places|place)/[\w\-]+/calsum/?$', ['GET']);
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

        $app[CurrentUser::class] = $app->share(
            static function (Application $app): CurrentUser {
                // Check first if we're impersonating someone.
                // This is done when handling async commands via a CLI worker.
                /* @var Impersonator $impersonator */
                $impersonator = $app['impersonator'];
                if ($impersonator->getUserId()) {
                    return new CurrentUser($impersonator->getUserId());
                }

                /* @var RequestAuthenticator $requestAuthenticator */
                $requestAuthenticator = $app[RequestAuthenticator::class];
                return $requestAuthenticator->getCurrentUser();
            }
        );

        $app[JsonWebToken::class] = $app::share(
            function (Application $app): ?JsonWebToken {
                // Check first if we're impersonating someone.
                // This is done when handling async commands via a CLI worker.
                /* @var Impersonator $impersonator */
                $impersonator = $app['impersonator'];
                if ($impersonator->getJwt()) {
                    return $impersonator->getJwt();
                }

                /* @var RequestAuthenticator $requestAuthenticator */
                $requestAuthenticator = $app[RequestAuthenticator::class];
                return $requestAuthenticator->getToken();
            }
        );

        $app[ApiKey::class] = $app->share(
            function (Application $app): ?ApiKey {
                // Check first if we're impersonating someone.
                // This is done when handling async commands via a CLI worker.
                /* @var Impersonator $impersonator */
                $impersonator = $app['impersonator'];
                if ($impersonator->getApiKey()) {
                    return $impersonator->getApiKey();
                }

                // If not impersonating then use the api key from the request.
                /** @var RequestAuthenticator $requestAuthenticator */
                $requestAuthenticator = $app[RequestAuthenticator::class];
                return $requestAuthenticator->getApiKey();
            }
        );

        $app[ConsumerReadRepository::class] = $app->share(
            function (Application $app): ConsumerReadRepository {
                return new InMemoryConsumerRepository(
                    new CultureFeedConsumerReadRepository($app['culturefeed'], true)
                );
            }
        );

        $app[Consumer::class] = $app->share(
            static function (Application $app): ?Consumer {
                $apiKey = $app[ApiKey::class];
                if ($apiKey === null) {
                    return null;
                }

                /** @var ConsumerReadRepository $consumerReadRepository */
                $consumerReadRepository = $app[ConsumerReadRepository::class];
                return $consumerReadRepository->getConsumer($apiKey);
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}

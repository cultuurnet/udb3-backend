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
use CultuurNet\UDB3\Jwt\JwtBaseValidator;
use CultuurNet\UDB3\Jwt\JwtV2Validator;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtAuthenticationProvider;
use CultuurNet\UDB3\Silex\Impersonator;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

final class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[RequestAuthenticator::class] = $app::share(
            function (Application $app): RequestAuthenticator {
                $authenticator = new RequestAuthenticator(
                    new JwtAuthenticationProvider(
                        new JwtBaseValidator(
                            'file://' . __DIR__ . '/../../' . $app['config']['jwt']['v1']['keys']['public']['file'],
                            ['uid'],
                            $app['config']['jwt']['v1']['valid_issuers']
                        ),
                        new JwtV2Validator(
                            new JwtBaseValidator(
                                'file://' . __DIR__ . '/../../' . $app['config']['jwt']['v2']['keys']['public']['file'],
                                ['sub'],
                                $app['config']['jwt']['v2']['valid_issuers']
                            ),
                            $app['config']['jwt']['v2']['jwt_provider_client_id']
                        )
                    ),
                    new CultureFeedApiKeyAuthenticator($app[ConsumerReadRepository::class]),
                    $app[ConsumerReadRepository::class],
                    new ConsumerIsInPermissionGroup((string) $app['config']['api_key']['group_id'])
                );

                // We can not expect the ids of events, places and organizers to be correctly formatted as UUIDs, because there
                // is no exhaustive documentation about how this is handled in UDB2. Therefore we take a rather liberal approach
                // and allow all alphanumeric characters and a dash as ids.
                $authenticator->addPublicRoute('~^/(events?|places?)/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/calendar-summary/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/(events?|places?)/[\w\-]+/permissions?/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/organizers/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/organizers/[\w\-]+/permissions/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/labels/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/label/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/media/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/images/[\w\-]+/?$~', ['GET']);
                $authenticator->addPublicRoute('~^/jobs~', ['GET']);
                $authenticator->addPublicRoute('~^/uitpas~', ['GET']);
                $authenticator->addPublicRoute('~^/news-articles~', ['GET', 'DELETE', 'POST', 'PUT']);

                // Legacy URLs that get rewritten. Still needed when we're still using the Silex router because in Silex the
                // rewrite happens after the auth check. But can be removed once we use the new PSR router for all routes
                // because on the new router the rewrite will happen before the auth check.
                $authenticator->addPublicRoute('^/(events|event|places|place)/[\w\-]+/calsum/?$', ['GET']);
                $authenticator->addPublicRoute('~^/news_articles~', ['GET', 'DELETE', 'POST', 'PUT']);

                return $authenticator;
            }
        );

        $app[CurrentUser::class] = $app->share(
            static function (Application $app): CurrentUser {
                /* @var Impersonator $impersonator */
                $impersonator = $app['impersonator'];
                $token = $app[JsonWebToken::class];

                $userId = null;
                if ($impersonator->getUserId()) {
                    $userId = $impersonator->getUserId();
                } elseif ($token instanceof JsonWebToken) {
                    $userId = $token->getUserId();
                }

                $isGodUser = $userId !== null &&
                    in_array($userId, $app['config']['user_permissions']['allow_all'], true);

                return new CurrentUser($userId, $isGodUser);
            }
        );

        $app[JsonWebToken::class] = $app::share(
            function (Application $app): ?JsonWebToken {
                // Check first if we're impersonating someone.
                /* @var Impersonator $impersonator */
                $impersonator = $app['impersonator'];
                if ($impersonator->getJwt()) {
                    return $impersonator->getJwt();
                }

                try {
                    /* @var RequestAuthenticator $requestAuthenticator */
                    $requestAuthenticator = $app[RequestAuthenticator::class];
                } catch (\InvalidArgumentException $e) {
                    // Running from CLI or unauthorized (will be further handled by the auth middleware)
                    return null;
                }

                return $requestAuthenticator->getToken();
            }
        );

        $app[ApiKey::class] = $app->share(
            function (Application $app): ?ApiKey {
                // Check first if we're impersonating someone.
                // This is done when handling commands.
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

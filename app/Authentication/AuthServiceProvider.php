<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Authentication;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\CultureFeedConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\Http\Auth\RequestAuthenticator;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Silex\Impersonator;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[CurrentUser::class] = $app->share(
            static function (Application $app): CurrentUser {
                return new CurrentUser($app['current_user_id'], $app['current_user_is_god_user']);
            }
        );

        $app['current_user_id'] = $app::share(
            function (Application $app): ?string {
                /* @var Impersonator $impersonator */
                $impersonator = $app['impersonator'];
                if ($impersonator->getUserId()) {
                    return $impersonator->getUserId();
                }

                $token = $app[JsonWebToken::class];
                if (!($token instanceof JsonWebToken)) {
                    // The token in the current request is missing (for example because it's a public route)
                    return null;
                }
                return $token->getUserId();
            }
        );

        $app['current_user_is_god_user'] = $app::share(
            function (Application $app): bool {
                return in_array(
                    $app['current_user_id'],
                    $app['config']['user_permissions']['allow_all'],
                    true
                );
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

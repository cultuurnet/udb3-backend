<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Authentication;

use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\CultureFeedConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\Http\Auth\RequestAuthenticator;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class AuthServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app[ConsumerReadRepository::class] = $app->share(
            function (Application $app): ConsumerReadRepository {
                return new InMemoryConsumerRepository(
                    new CultureFeedConsumerReadRepository($app['culturefeed'], true)
                );
            }
        );

        $app[Consumer::class] = $app->share(
            static function (Application $app) {
                /** @var ConsumerReadRepository $consumerReadRepository */
                $consumerReadRepository = $app[ConsumerReadRepository::class];
                return $consumerReadRepository->getConsumer($app['auth.api_key']);
            }
        );

        $app['auth.api_key'] = $app->share(
            static function (Application $app) {
                /** @var RequestAuthenticator $requestAuthenticator */
                $requestAuthenticator = $app[RequestAuthenticator::class];
                return $requestAuthenticator->getApiKey();
            }
        );
    }


    public function boot(Application $app)
    {
    }
}

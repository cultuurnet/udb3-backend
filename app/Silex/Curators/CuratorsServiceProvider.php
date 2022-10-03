<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use CultuurNet\UDB3\Curators\DBALNewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\Curators\CreateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\DeleteNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticlesRequestHandler;
use CultuurNet\UDB3\Http\Curators\UpdateNewsArticleRequestHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class CuratorsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[NewsArticleRepository::class] = $app->share(
            fn (Application $app) => new DBALNewsArticleRepository($app['dbal_connection'])
        );

        $app[GetNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new GetNewsArticleRequestHandler(
                $app[NewsArticleRepository::class]
            )
        );

        $app[GetNewsArticlesRequestHandler::class] = $app->share(
            fn (Application $application) => new GetNewsArticlesRequestHandler(
                $app[NewsArticleRepository::class]
            )
        );

        $app[CreateNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new CreateNewsArticleRequestHandler(
                $app[NewsArticleRepository::class],
                $app['uuid_generator'],
            )
        );

        $app[UpdateNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateNewsArticleRequestHandler(
                $app[NewsArticleRepository::class],
            )
        );

        $app[DeleteNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new DeleteNewsArticleRequestHandler(
                $app[NewsArticleRepository::class],
            )
        );
    }

    public function boot(Application $app)
    {
    }
}

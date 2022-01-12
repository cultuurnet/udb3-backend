<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Http\Curators\CreateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\DeleteNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticlesRequestHandler;
use CultuurNet\UDB3\Http\Curators\UpdateNewsArticleRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class CuratorsControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', GetNewsArticlesRequestHandler::class);
        $controllers->get('/{articleId}/', GetNewsArticleRequestHandler::class);

        $controllers->post('/', CreateNewsArticleRequestHandler::class);
        $controllers->put('/{articleId}', UpdateNewsArticleRequestHandler::class);

        $controllers->delete('/{articleId}/', DeleteNewsArticleRequestHandler::class);

        return $controllers;
    }

    public function register(Application $app): void
    {
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

    public function boot(Application $app): void
    {
    }
}

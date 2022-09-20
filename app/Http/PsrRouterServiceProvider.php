<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use CultuurNet\UDB3\Http\Curators\CreateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\DeleteNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticlesRequestHandler;
use CultuurNet\UDB3\Http\Curators\UpdateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\CustomLeagueRouterStrategy;
use CultuurNet\UDB3\Http\Export\ExportEventsAsJsonLdRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsOoXmlRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsPdfRequestHandler;
use CultuurNet\UDB3\Http\InvokableRequestHandlerContainer;
use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
use CultuurNet\UDB3\Http\Productions\AddEventToProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\MergeProductionsRequestHandler;
use CultuurNet\UDB3\Http\Productions\RemoveEventFromProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\RenameProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\SearchProductionsRequestHandler;
use CultuurNet\UDB3\Http\Productions\SkipEventsRequestHandler;
use CultuurNet\UDB3\Http\Productions\SuggestProductionRequestHandler;
use CultuurNet\UDB3\Silex\PimplePSRContainerBridge;
use League\Route\RouteGroup;
use League\Route\Router;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class PsrRouterServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[Router::class] = $app::share(
            function (Application $app) {
                $router = new Router();

                // Create a PSR container based on the Silex (Pimple) container, to allow the router to resolve
                // request handler class names to actual instances.
                $container = new PimplePSRContainerBridge($app);

                // Decorate the PSR container with InvokableRequestHandlerContainer so that every
                // RequestHandlerInterface that gets requested by the router is decorated with InvokableRequestHandler,
                // because the League router needs the router to be a callable at the time of writing.
                $container = new InvokableRequestHandlerContainer($container);

                // Use a custom strategy so we can implement getOptionsCallable() on the strategy, to support CORS
                // pre-flight requests. We also have to set the container on the strategy.
                $routerStrategy = new CustomLeagueRouterStrategy();
                $routerStrategy->setContainer($container);
                $router->setStrategy($routerStrategy);

                $this->bindCurators($router);

                $this->bindProductions($router);

                $this->bindExports($router);

                $router->get('/{offerType:events|places}/{offerId}/', GetDetailRequestHandler::class);

                return $router;
            }
        );
    }

    private function bindCurators(Router $router): void
    {
        $router->group('news-articles', function (RouteGroup $routeGroup) {
            $routeGroup->get('', GetNewsArticlesRequestHandler::class);
            $routeGroup->get('{articleId}/', GetNewsArticleRequestHandler::class);

            $routeGroup->post('', CreateNewsArticleRequestHandler::class);
            $routeGroup->put('{articleId}/', UpdateNewsArticleRequestHandler::class);

            $routeGroup->delete('{articleId}/', DeleteNewsArticleRequestHandler::class);
        });
    }

    private function bindProductions(Router $router): void
    {
        $router->group('productions', function (RouteGroup $routeGroup) {
            $routeGroup->get('', SearchProductionsRequestHandler::class);

            $routeGroup->post('', CreateProductionRequestHandler::class);
            $routeGroup->put('{productionId}/events/{eventId}/', AddEventToProductionRequestHandler::class);
            $routeGroup->delete('{productionId}/events/{eventId}/', RemoveEventFromProductionRequestHandler::class);
            $routeGroup->post('{productionId}/merge/{fromProductionId}/', MergeProductionsRequestHandler::class);
            $routeGroup->put('{productionId}/name/', RenameProductionRequestHandler::class);

            $routeGroup->post('skip/', SkipEventsRequestHandler::class);

            $routeGroup->get('suggestion/', SuggestProductionRequestHandler::class);
        });
    }

    private function bindExports(Router $router): void
    {
        $router->group('events/export', function (RouteGroup $routeGroup) {
            $routeGroup->post('json/', ExportEventsAsJsonLdRequestHandler::class);
            $routeGroup->post('ooxml/', ExportEventsAsOoXmlRequestHandler::class);
            $routeGroup->post('pdf/', ExportEventsAsPdfRequestHandler::class);
        });
    }

    public function boot(Application $app): void
    {
    }
}

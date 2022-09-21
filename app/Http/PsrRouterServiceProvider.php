<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use CultuurNet\UDB3\Http\Curators\CreateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\DeleteNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticlesRequestHandler;
use CultuurNet\UDB3\Http\Curators\UpdateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Event\CopyEventRequestHandler;
use CultuurNet\UDB3\Http\Event\DeleteOnlineUrlRequestHandler;
use CultuurNet\UDB3\Http\Event\DeleteThemeRequestHandler;
use CultuurNet\UDB3\Http\Event\ImportEventRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateAttendanceModeRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateAudienceRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateLocationRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateMajorInfoRequestHandler as UpdateEventMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateOnlineUrlRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateSubEventsRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateThemeRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsJsonLdRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsOoXmlRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsPdfRequestHandler;
use CultuurNet\UDB3\Http\Place\GetEventsRequestHandler;
use CultuurNet\UDB3\Http\Place\UpdateAddressRequestHandler as UpdatePlaceAddressRequestHandler;
use CultuurNet\UDB3\Http\Place\UpdateMajorInfoRequestHandler as UpdatePlaceMajorInfoRequestHandler;
use CultuurNet\UDB3\Silex\Error\WebErrorHandler;
use CultuurNet\UDB3\Http\InvokableRequestHandlerContainer;
use CultuurNet\UDB3\Http\Jobs\GetJobStatusRequestHandler;
use CultuurNet\UDB3\Http\Label\CreateLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\GetLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\PatchLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\SearchLabelsRequestHandler;
use CultuurNet\UDB3\Http\Media\GetMediaRequestHandler;
use CultuurNet\UDB3\Http\Media\UploadMediaRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
use CultuurNet\UDB3\Http\Organizer\AddImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\AddLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\ImportOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateAddressRequestHandler as UpdateOrganizerAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateContactPointRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateImagesRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateMainImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateUrlRequestHandler;
use CultuurNet\UDB3\Http\Place\ImportPlaceRequestHandler;
use CultuurNet\UDB3\Http\Productions\AddEventToProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\MergeProductionsRequestHandler;
use CultuurNet\UDB3\Http\Productions\RemoveEventFromProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\RenameProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\SearchProductionsRequestHandler;
use CultuurNet\UDB3\Http\Productions\SkipEventsRequestHandler;
use CultuurNet\UDB3\Http\Productions\SuggestProductionRequestHandler;
use CultuurNet\UDB3\Http\Proxy\ProxyRequestHandler;
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
                $routerStrategy = new CustomLeagueRouterStrategy($app[WebErrorHandler::class]);
                $routerStrategy->setContainer($container);
                $router->setStrategy($routerStrategy);

                $this->bindNewsArticles($router);

                $this->bindProductions($router);

                $this->bindExports($router);

                $this->bindLegacyImports($router);

                $this->bindJobs($router);

                $this->bindLabels($router);

                $this->bindImages($router);

                $this->bindOrganizers($router);

                $this->bindEvents($router);

                $this->bindPlaces($router);

                // Proxy GET requests to /events, /places, /offers and /organizers to SAPI3.
                $router->get('/events/', ProxyRequestHandler::class);
                $router->get('/places/', ProxyRequestHandler::class);
                $router->get('/offers/', ProxyRequestHandler::class);
                $router->get('/organizers/', ProxyRequestHandler::class);

                $router->get('/{offerType:events|places}/{offerId}/', GetDetailRequestHandler::class);

                return $router;
            }
        );
    }

    private function bindNewsArticles(Router $router): void
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

    private function bindLegacyImports(Router $router): void
    {
        // Bind the `/imports/...` routes for backwards compatibility.
        $router->group('imports', function (RouteGroup $routeGroup) {
            $routeGroup->post('events/', ImportEventRequestHandler::class);
            $routeGroup->put('events/{eventId}/', ImportEventRequestHandler::class);

            $routeGroup->post('places/', ImportPlaceRequestHandler::class);
            $routeGroup->put('places/{placeId}/', ImportPlaceRequestHandler::class);

            $routeGroup->post('organizers/', ImportOrganizerRequestHandler::class);
            $routeGroup->put('organizers/{organizerId}/', ImportOrganizerRequestHandler::class);
        });
    }

    private function bindJobs(Router $router): void
    {
        $router->group('jobs', function (RouteGroup $routeGroup) {
            $routeGroup->get('{jobId}/', GetJobStatusRequestHandler::class);
        });
    }

    private function bindLabels(Router $router): void
    {
        $router->group('labels', function (RouteGroup $routeGroup) {
            $routeGroup->post('', CreateLabelRequestHandler::class);
            $routeGroup->patch('{labelId}/', PatchLabelRequestHandler::class);

            $routeGroup->get('{labelId}/', GetLabelRequestHandler::class);
            $routeGroup->get('', SearchLabelsRequestHandler::class);
        });
    }

    private function bindImages(Router $router): void
    {
        $router->group('images', function (RouteGroup $routeGroup) {
            $routeGroup->post('', UploadMediaRequestHandler::class);
            $routeGroup->get('{id}/', GetMediaRequestHandler::class);
        });

        /* @deprecated */
        $router->get('/media/{id}/', GetMediaRequestHandler::class);
    }

    private function bindOrganizers(Router $router): void
    {
        $router->group('organizers', function (RouteGroup $routeGroup) {
            $routeGroup->post('', ImportOrganizerRequestHandler::class);
            $routeGroup->put('{organizerId}/', ImportOrganizerRequestHandler::class);
            $routeGroup->get('{organizerId}/', GetOrganizerRequestHandler::class);
            $routeGroup->delete('{organizerId}/', DeleteOrganizerRequestHandler::class);

            $routeGroup->put('{organizerId}/name/', UpdateTitleRequestHandler::class);
            $routeGroup->put('{organizerId}/name/{language}/', UpdateTitleRequestHandler::class);

            $routeGroup->put('{organizerId}/description/{language}/', UpdateDescriptionRequestHandler::class);
            $routeGroup->delete('{organizerId}/description/{language}/', DeleteDescriptionRequestHandler::class);

            $routeGroup->put('{organizerId}/address/', UpdateOrganizerAddressRequestHandler::class);
            $routeGroup->put('{organizerId}/address/{language}/', UpdateOrganizerAddressRequestHandler::class);
            $routeGroup->delete('{organizerId}/address/', DeleteAddressRequestHandler::class);

            $routeGroup->put('{organizerId}/url/', UpdateUrlRequestHandler::class);

            $routeGroup->put('{organizerId}/contact-point/', UpdateContactPointRequestHandler::class);

            $routeGroup->post('{organizerId}/images/', AddImageRequestHandler::class);
            $routeGroup->put('{organizerId}/images/main/', UpdateMainImageRequestHandler::class);
            $routeGroup->patch('{organizerId}/images/', UpdateImagesRequestHandler::class);
            $routeGroup->delete('{organizerId}/images/{imageId}/', DeleteImageRequestHandler::class);

            $routeGroup->put('{organizerId}/labels/{labelName}/', AddLabelRequestHandler::class);
            $routeGroup->delete('{organizerId}/labels/{labelName}/', DeleteLabelRequestHandler::class);

            $routeGroup->get('{organizerId}/permissions/', GetPermissionsForCurrentUserRequestHandler::class);
            $routeGroup->get('{organizerId}/permissions/{userId}/', GetPermissionsForGivenUserRequestHandler::class);
        });
    }

    private function bindEvents(Router $router): void
    {
        $router->group('events', function (RouteGroup $routeGroup) {
            $routeGroup->post('', ImportEventRequestHandler::class);
            $routeGroup->put('{eventId}/', ImportEventRequestHandler::class);

            $routeGroup->put('{eventId}/major-info/', UpdateEventMajorInfoRequestHandler::class);
            $routeGroup->put('{eventId}/location/{locationId}/', UpdateLocationRequestHandler::class);
            $routeGroup->patch('{eventId}/sub-events/', UpdateSubEventsRequestHandler::class);
            $routeGroup->put('{eventId}/theme/{termId}/', UpdateThemeRequestHandler::class);
            $routeGroup->delete('{eventId}/theme/', DeleteThemeRequestHandler::class);
            $routeGroup->put('{eventId}/attendance-mode/', UpdateAttendanceModeRequestHandler::class);
            $routeGroup->put('{eventId}/online-url/', UpdateOnlineUrlRequestHandler::class);
            $routeGroup->delete('{eventId}/online-url/', DeleteOnlineUrlRequestHandler::class);
            $routeGroup->put('{eventId}/audience/', UpdateAudienceRequestHandler::class);
            $routeGroup->post('{eventId}/copies/', CopyEventRequestHandler::class);

            /**
             * Legacy routes that we need to keep for backward compatibility.
             * These routes usually used an incorrect HTTP method.
             */
            $routeGroup->post('{eventId}/major-info/', UpdateEventMajorInfoRequestHandler::class);
        });
    }

    private function bindPlaces(Router $router): void
    {
        $router->group('places', function (RouteGroup $routeGroup) {
            $routeGroup->post('', ImportPlaceRequestHandler::class);
            $routeGroup->put('{placeId}/', ImportPlaceRequestHandler::class);

            $routeGroup->put('{placeId}/address/{language}/', UpdatePlaceAddressRequestHandler::class);
            $routeGroup->put('{placeId}/major-info/', UpdatePlaceMajorInfoRequestHandler::class);

            /**
             * Legacy routes that we need to keep for backward compatibility.
             * These routes usually used an incorrect HTTP method.
             */
            $routeGroup->get('{placeId}/events/', GetEventsRequestHandler::class);
            $routeGroup->post('{placeId}/address/{language}/', UpdatePlaceAddressRequestHandler::class);
            $routeGroup->post('{placeId}/major-info/', UpdatePlaceMajorInfoRequestHandler::class);
        });
    }

    public function boot(Application $app): void
    {
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Http\Auth\RequestAuthenticatorMiddleware;
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
use CultuurNet\UDB3\Http\Offer\AddImageRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelFromJsonBodyRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelToMultipleRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelToQueryRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\CurrentUserHasPermissionRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteDescriptionRequestHandler as DeleteDescriptionOfferRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteOrganizerRequestHandler as DeleteOfferOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteTypicalAgeRangeRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetCalendarSummaryRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetContributorsRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetHistoryRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetPermissionsForCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Offer\GivenUserHasPermissionRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateContributorsRequestHandler;
use CultuurNet\UDB3\Http\Offer\PatchOfferRequestHandler;
use CultuurNet\UDB3\Http\Offer\RemoveImageRequestHandler;
use CultuurNet\UDB3\Http\Offer\RemoveLabelRequestHandler;
use CultuurNet\UDB3\Http\Offer\SelectMainImageRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateAvailableFromRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateBookingAvailabilityRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateBookingInfoRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateCalendarRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateContactPointRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateFacilitiesRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateImageRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateOrganizerFromJsonBodyRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdatePriceInfoRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateStatusRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateTypeRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateTypicalAgeRangeRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateVideosRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateWorkflowStatusRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteEducationalDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetCreatorRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateEducationalDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Ownership\ApproveOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\DeleteOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\GetOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\SuggestOwnershipsRequestHandler;
use CultuurNet\UDB3\Http\Ownership\RejectOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\RequestOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\SearchOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Place\GetEventsRequestHandler;
use CultuurNet\UDB3\Http\Place\UpdateAddressRequestHandler as UpdatePlaceAddressRequestHandler;
use CultuurNet\UDB3\Http\Place\UpdateMajorInfoRequestHandler as UpdatePlaceMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Role\AddConstraintRequestHandler;
use CultuurNet\UDB3\Http\Role\AddLabelToRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\AddPermissionToRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\AddRoleToUserRequestHandler;
use CultuurNet\UDB3\Http\Role\CreateRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\DeleteConstraintRequestHandler;
use CultuurNet\UDB3\Http\Role\DeleteRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\GetPermissionsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRoleLabelsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRolesFromCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRolesFromUserRequestHandler;
use CultuurNet\UDB3\Http\Role\GetUserPermissionsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetUsersWithRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\RemoveLabelFromRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\RemovePermissionFromRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\RemoveRoleFromUserRequestHandler;
use CultuurNet\UDB3\Http\Role\RolesSearchRequestHandler;
use CultuurNet\UDB3\Http\Role\UpdateConstraintRequestHandler;
use CultuurNet\UDB3\Http\Role\UpdateRoleRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\CreateSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\DeleteSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\ReadSavedSearchesRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\UpdateSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\User\GetCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\User\GetUserByEmailRequestHandler;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\WebErrorHandler;
use CultuurNet\UDB3\Http\Jobs\GetJobStatusRequestHandler;
use CultuurNet\UDB3\Http\Label\CreateLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\GetLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\PatchLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\SearchLabelsRequestHandler;
use CultuurNet\UDB3\Http\Media\GetMediaRequestHandler;
use CultuurNet\UDB3\Http\Media\UploadMediaRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
use CultuurNet\UDB3\Http\Organizer\AddImageRequestHandler as AddOrganizerImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\AddLabelRequestHandler as AddOrganizerLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteLabelRequestHandler;
use CultuurNet\UDB3\Http\Organizer\DeleteOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetContributorsRequestHandler as GetContributorsOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForCurrentUserRequestHandler as GetOrganizerPermissionsForCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\GetPermissionsForGivenUserRequestHandler as GetOrganizerPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Organizer\ImportOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateContributorsRequestHandler as UpdateContributorsOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateAddressRequestHandler as UpdateOrganizerAddressRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateContactPointRequestHandler as UpdateOrganizerContactPointRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateDescriptionRequestHandler as UpdateOrganizerDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateImagesRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateMainImageRequestHandler;
use CultuurNet\UDB3\Http\Organizer\UpdateTitleRequestHandler as UpdateOrganizerTitleRequestHandler;
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
use CultuurNet\UDB3\Mailinglist\SubscribeUserToMailinglistRequestHandler;
use CultuurNet\UDB3\Taxonomy\GetEducationLevelsRequestHandler;
use CultuurNet\UDB3\Taxonomy\GetRegionsRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\AddCardSystemToEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\DeleteCardSystemFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromOrganizerRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASDetailRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASLabelsRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\SetCardSystemsOnEventRequestHandler;
use League\Route\RouteGroup;
use League\Route\Router;
use Psr\Container\ContainerInterface;

final class PsrRouterServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [Router::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            Router::class,
            function () use ($container) {
                $router = new Router();

                // Decorate the PSR container with InvokableRequestHandlerContainer so that every
                // RequestHandlerInterface that gets requested by the router is decorated with InvokableRequestHandler,
                // because the League router needs the router to be a callable at the time of writing.
                $container = new InvokableRequestHandlerContainer($container);

                // Use a custom strategy so we can implement getOptionsCallable() on the strategy, to support CORS
                // pre-flight requests. We also have to set the container on the strategy.
                $routerStrategy = new CustomLeagueRouterStrategy($container->get(WebErrorHandler::class));
                $routerStrategy->setContainer($container);
                $router->setStrategy($routerStrategy);

                $this->registerMiddlewares($container, $router);

                $this->bindOffers($router);

                $this->bindEvents($router);

                $this->bindPlaces($router);

                $this->bindOrganizers($router);

                if ($container->get('config')['enable_ownership_endpoints'] ?? false) {
                    $this->bindOwnerships($router);
                }

                $this->bindNewsArticles($router);

                $this->bindProductions($router);

                $this->bindLabels($router);

                $this->bindRoles($router);

                $this->bindExports($router);

                $this->bindLegacyImports($router);

                $this->bindJobs($router);

                $this->bindImages($router);

                $this->bindUser($router);

                $this->bindSavedSearches($router);

                $this->bindUiTPASEvents($router);

                $this->bindUiTPASLabels($router);

                $this->bindUiTPASOrganizers($router);

                $this->bindMailinglist($router);

                $this->bindTaxonomyEndpoints($router);

                // Proxy GET requests to /events, /places, /offers and /organizers to SAPI3.
                $router->get('/events/', ProxyRequestHandler::class);
                $router->get('/places/', ProxyRequestHandler::class);
                $router->get('/offers/', ProxyRequestHandler::class);
                $router->get('/organizers/', ProxyRequestHandler::class);

                return $router;
            }
        );
    }

    private function registerMiddlewares(ContainerInterface $container, Router $router): void
    {
        // Intercepts all "ProjectedToJSONLD" messages during request handling, and publishes the unique ones on the
        // event bus afterwards. See class docblock for more info.
        $router->middleware(new ProjectedToJSONLDInterceptingMiddleware($container->get(EventBus::class)));

        // Determines if a request requires authentication or not, and if yes it checks the JWT and optionally the API
        // key to determine if the request is correctly authenticated.
        $router->middleware($container->get(RequestAuthenticatorMiddleware::class));

        $router->middleware($container->get(CheckTypeOfOfferMiddleware::class));
        $router->middleware($container->get(CheckOrganizerMiddleware::class));
    }

    private function bindNewsArticles(Router $router): void
    {
        $router->group('news-articles', function (RouteGroup $routeGroup): void {
            $routeGroup->get('', GetNewsArticlesRequestHandler::class);
            $routeGroup->get('{articleId}/', GetNewsArticleRequestHandler::class);

            $routeGroup->post('', CreateNewsArticleRequestHandler::class);
            $routeGroup->put('{articleId}/', UpdateNewsArticleRequestHandler::class);

            $routeGroup->delete('{articleId}/', DeleteNewsArticleRequestHandler::class);
        });
    }

    private function bindProductions(Router $router): void
    {
        $router->group('productions', function (RouteGroup $routeGroup): void {
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
        $router->group('events/export', function (RouteGroup $routeGroup): void {
            $routeGroup->post('json/', ExportEventsAsJsonLdRequestHandler::class);
            $routeGroup->post('ooxml/', ExportEventsAsOoXmlRequestHandler::class);
            $routeGroup->post('pdf/', ExportEventsAsPdfRequestHandler::class);
        });
    }

    private function bindLegacyImports(Router $router): void
    {
        // Bind the `/imports/...` routes for backwards compatibility.
        $router->group('imports', function (RouteGroup $routeGroup): void {
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
        $router->group('jobs', function (RouteGroup $routeGroup): void {
            $routeGroup->get('{jobId}/', GetJobStatusRequestHandler::class);
        });
    }

    private function bindLabels(Router $router): void
    {
        $router->post('/query/labels/', AddLabelToQueryRequestHandler::class);
        $router->post('/offers/labels/', AddLabelToMultipleRequestHandler::class);

        $router->group('labels', function (RouteGroup $routeGroup): void {
            $routeGroup->post('', CreateLabelRequestHandler::class);
            $routeGroup->patch('{labelId}/', PatchLabelRequestHandler::class);

            $routeGroup->get('{labelId}/', GetLabelRequestHandler::class);
            $routeGroup->get('', SearchLabelsRequestHandler::class);
        });
    }

    private function bindImages(Router $router): void
    {
        $router->group('images', function (RouteGroup $routeGroup): void {
            $routeGroup->post('', UploadMediaRequestHandler::class);
            $routeGroup->get('{id}/', GetMediaRequestHandler::class);
        });

        /* @deprecated */
        $router->get('/media/{id}/', GetMediaRequestHandler::class);
    }

    private function bindOrganizers(Router $router): void
    {
        $router->group('organizers', function (RouteGroup $routeGroup): void {
            $routeGroup->post('', ImportOrganizerRequestHandler::class);
            $routeGroup->put('{organizerId}/', ImportOrganizerRequestHandler::class);
            $routeGroup->get('{organizerId}/', GetOrganizerRequestHandler::class);
            $routeGroup->delete('{organizerId}/', DeleteOrganizerRequestHandler::class);

            $routeGroup->put('{organizerId}/name/', UpdateOrganizerTitleRequestHandler::class);
            $routeGroup->put('{organizerId}/name/{language}/', UpdateOrganizerTitleRequestHandler::class);

            $routeGroup->put('{organizerId}/description/{language}/', UpdateOrganizerDescriptionRequestHandler::class);
            $routeGroup->delete('{organizerId}/description/{language}/', DeleteDescriptionRequestHandler::class);

            $routeGroup->put('{organizerId}/educational-description/{language}/', UpdateEducationalDescriptionRequestHandler::class);
            $routeGroup->delete('{organizerId}/educational-description/{language}/', DeleteEducationalDescriptionRequestHandler::class);

            $routeGroup->put('{organizerId}/address/', UpdateOrganizerAddressRequestHandler::class);
            $routeGroup->put('{organizerId}/address/{language}/', UpdateOrganizerAddressRequestHandler::class);
            $routeGroup->delete('{organizerId}/address/', DeleteAddressRequestHandler::class);

            $routeGroup->get('{organizerId}/creator/', GetCreatorRequestHandler::class);

            $routeGroup->put('{organizerId}/url/', UpdateUrlRequestHandler::class);

            $routeGroup->put('{organizerId}/contact-point/', UpdateOrganizerContactPointRequestHandler::class);

            $routeGroup->post('{organizerId}/images/', AddOrganizerImageRequestHandler::class);
            $routeGroup->put('{organizerId}/images/main/', UpdateMainImageRequestHandler::class);
            $routeGroup->patch('{organizerId}/images/', UpdateImagesRequestHandler::class);
            $routeGroup->delete('{organizerId}/images/{imageId}/', DeleteImageRequestHandler::class);

            $routeGroup->put('{organizerId}/labels/{labelName}/', AddOrganizerLabelRequestHandler::class);
            $routeGroup->delete('{organizerId}/labels/{labelName}/', DeleteLabelRequestHandler::class);

            $routeGroup->get('{organizerId}/permissions/', GetOrganizerPermissionsForCurrentUserRequestHandler::class);
            $routeGroup->get('{organizerId}/permissions/{userId}/', GetOrganizerPermissionsForGivenUserRequestHandler::class);

            $routeGroup->get('{organizerId}/contributors/', GetContributorsOrganizerRequestHandler::class);
            $routeGroup->put('{organizerId}/contributors/', UpdateContributorsOrganizerRequestHandler::class);
        });
    }

    private function bindOwnerships(Router $router): void
    {
        $router->group('ownerships', function (RouteGroup $routeGroup): void {
            $routeGroup->get('', SearchOwnershipRequestHandler::class);

            $routeGroup->get('suggestions/', SuggestOwnershipsRequestHandler::class);

            $routeGroup->get('{ownershipId}/', GetOwnershipRequestHandler::class);

            $routeGroup->post('', RequestOwnershipRequestHandler::class);

            $routeGroup->post('{ownershipId}/approve/', ApproveOwnershipRequestHandler::class);
            $routeGroup->post('{ownershipId}/reject/', RejectOwnershipRequestHandler::class);

            $routeGroup->delete('{ownershipId}/', DeleteOwnershipRequestHandler::class);
        });
    }

    private function bindOffers(Router $router): void
    {
        $router->get('/{offerType:events|places}/{offerId}/', GetDetailRequestHandler::class);
        $router->delete('/{offerType:events|places}/{offerId}/', DeleteRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/name/{language}/', UpdateTitleRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/{language}/title/', UpdateTitleRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/description/{language}/', UpdateDescriptionRequestHandler::class);
        $router->delete('/{offerType:events|places}/{offerId}/description/{language}/', DeleteDescriptionOfferRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/available-from/', UpdateAvailableFromRequestHandler::class);

        $router->get('/{offerType:events|places}/{offerId}/history/', GetHistoryRequestHandler::class);

        $router->get('/{offerType:events|places}/{offerId}/permissions/', GetPermissionsForCurrentUserRequestHandler::class);
        $router->get('/{offerType:events|places}/{offerId}/permissions/{userId}/', GetPermissionsForGivenUserRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/calendar/', UpdateCalendarRequestHandler::class);
        $router->get('/{offerType:events|places}/{offerId}/calendar-summary/', GetCalendarSummaryRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/contact-point/', UpdateContactPointRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/status/', UpdateStatusRequestHandler::class);
        $router->put('/{offerType:events|places}/{offerId}/booking-availability/', UpdateBookingAvailabilityRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/type/{termId}/', UpdateTypeRequestHandler::class);
        $router->put('/{offerType:events|places}/{offerId}/facilities/', UpdateFacilitiesRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/typical-age-range/', UpdateTypicalAgeRangeRequestHandler::class);
        $router->delete('/{offerType:events|places}/{offerId}/typical-age-range/', DeleteTypicalAgeRangeRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/booking-info/', UpdateBookingInfoRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/labels/{labelName}/', AddLabelRequestHandler::class);
        $router->delete('/{offerType:events|places}/{offerId}/labels/{labelName}/', RemoveLabelRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/price-info/', UpdatePriceInfoRequestHandler::class);

        $router->post('/{offerType:events|places}/{offerId}/images/', AddImageRequestHandler::class);
        $router->put('/{offerType:events|places}/{offerId}/images/main/', SelectMainImageRequestHandler::class);
        $router->put('/{offerType:events|places}/{offerId}/images/{mediaId}/', UpdateImageRequestHandler::class);
        $router->delete('/{offerType:events|places}/{offerId}/images/{mediaId}/', RemoveImageRequestHandler::class);

        $router->post('/{offerType:events|places}/{offerId}/videos/', AddVideoRequestHandler::class);
        $router->patch('/{offerType:events|places}/{offerId}/videos/', UpdateVideosRequestHandler::class);
        $router->delete('/{offerType:events|places}/{offerId}/videos/{videoId}/', DeleteVideoRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/organizer/{organizerId}/', UpdateOrganizerRequestHandler::class);
        $router->delete('/{offerType:events|places}/{offerId}/organizer/{organizerId}/', DeleteOfferOrganizerRequestHandler::class);

        $router->put('/{offerType:events|places}/{offerId}/workflow-status/', UpdateWorkflowStatusRequestHandler::class);

        $router->get('/{offerType:events|places}/{offerId}/contributors/', GetContributorsRequestHandler::class);
        $router->put('/{offerType:events|places}/{offerId}/contributors/', UpdateContributorsRequestHandler::class);

        $router->patch('/{offerType:events|places}/{offerId}/', PatchOfferRequestHandler::class);

        /**
         * Legacy routes that we need to keep for backward compatibility.
         * These routes usually, but not always, used an incorrect HTTP method.
         */
        $router->get('/{offerType:events|places}/{offerId}/permission/', CurrentUserHasPermissionRequestHandler::class);
        $router->get('/{offerType:events|places}/{offerId}/permission/{userId}/', GivenUserHasPermissionRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/typical-age-range/', UpdateTypicalAgeRangeRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/booking-info/', UpdateBookingInfoRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/contact-point/', UpdateContactPointRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/organizer/', UpdateOrganizerFromJsonBodyRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/images/main/', SelectMainImageRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/images/{mediaId}/', UpdateImageRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/labels/', AddLabelFromJsonBodyRequestHandler::class);
        $router->post('/{offerType:events|places}/{offerId}/{language}/description/', UpdateDescriptionRequestHandler::class);
    }

    private function bindEvents(Router $router): void
    {
        $router->group('events', function (RouteGroup $routeGroup): void {
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
        $router->group('places', function (RouteGroup $routeGroup): void {
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

    private function bindUser(Router $router): void
    {
        $router->get('/users/emails/{email}/', GetUserByEmailRequestHandler::class);
        $router->get('/user/', GetCurrentUserRequestHandler::class);
    }

    private function bindRoles(Router $router): void
    {
        $router->get('/roles/', RolesSearchRequestHandler::class);
        $router->get('/roles/{roleId}/', GetRoleRequestHandler::class);
        $router->get('/permissions/', GetPermissionsRequestHandler::class);
        $router->get('/roles/{roleId}/users/', GetUsersWithRoleRequestHandler::class);
        $router->get('/roles/{roleId}/labels/', GetRoleLabelsRequestHandler::class);
        $router->get('/users/{userId}/roles/', GetRolesFromUserRequestHandler::class);
        $router->get('/user/roles/', GetRolesFromCurrentUserRequestHandler::class);
        $router->get('/user/permissions/', GetUserPermissionsRequestHandler::class);

        $router->post('/roles/', CreateRoleRequestHandler::class);
        $router->patch('/roles/{roleId}/', UpdateRoleRequestHandler::class);
        $router->post('/roles/{roleId}/constraints/', AddConstraintRequestHandler::class);
        $router->put('/roles/{roleId}/constraints/', UpdateConstraintRequestHandler::class);
        $router->delete('/roles/{roleId}/constraints/', DeleteConstraintRequestHandler::class);
        $router->delete('/roles/{roleId}/', DeleteRoleRequestHandler::class);
        $router->put('/roles/{roleId}/permissions/{permissionKey}/', AddPermissionToRoleRequestHandler::class);
        $router->delete('/roles/{roleId}/permissions/{permissionKey}/', RemovePermissionFromRoleRequestHandler::class);
        $router->put('/roles/{roleId}/labels/{labelId}/', AddLabelToRoleRequestHandler::class);
        $router->delete('/roles/{roleId}/labels/{labelId}/', RemoveLabelFromRoleRequestHandler::class);
        $router->put('/roles/{roleId}/users/{userId}/', AddRoleToUserRequestHandler::class);
        $router->delete('/roles/{roleId}/users/{userId}/', RemoveRoleFromUserRequestHandler::class);
    }

    private function bindSavedSearches(Router $router): void
    {
        $router->group('saved-searches', function (RouteGroup $routeGroup): void {
            $routeGroup->get('v3/', ReadSavedSearchesRequestHandler::class);

            $routeGroup->post('v3/', CreateSavedSearchRequestHandler::class);
            $routeGroup->put('v3/{id}/', UpdateSavedSearchRequestHandler::class);

            $routeGroup->delete('v3/{id}/', DeleteSavedSearchRequestHandler::class);
        });
    }

    private function bindUiTPASEvents(Router $router): void
    {
        $router->group('uitpas/events', function (RouteGroup $routeGroup): void {
            $routeGroup->get('{eventId}/', GetUiTPASDetailRequestHandler::class);

            $routeGroup->get('{eventId}/card-systems/', GetCardSystemsFromEventRequestHandler::class);

            $routeGroup->put('{eventId}/card-systems/', SetCardSystemsOnEventRequestHandler::class);

            $routeGroup->put('{eventId}/card-systems/{cardSystemId}/', AddCardSystemToEventRequestHandler::class);

            $routeGroup->put(
                '{eventId}/card-systems/{cardSystemId}/distribution-key/{distributionKeyId}/',
                AddCardSystemToEventRequestHandler::class
            );

            $routeGroup->delete('{eventId}/card-systems/{cardSystemId}/', DeleteCardSystemFromEventRequestHandler::class);
        });
    }

    private function bindUiTPASLabels(Router $router): void
    {
        $router->group('uitpas/labels', function (RouteGroup $routeGroup): void {
            $routeGroup->get('', GetUiTPASLabelsRequestHandler::class);
        });
    }

    private function bindUiTPASOrganizers(Router $router): void
    {
        $router->group('uitpas/organizers', function (RouteGroup $routeGroup): void {
            $routeGroup->get('{organizerId}/card-systems/', GetCardSystemsFromOrganizerRequestHandler::class);
        });
    }

    private function bindMailinglist(Router $router): void
    {
        $router->put('mailing-list/{mailingListId}/', SubscribeUserToMailinglistRequestHandler::class);
    }

    private function bindTaxonomyEndpoints(Router $router): void
    {
        $router->get('regions/', GetRegionsRequestHandler::class);

        $router->get('education-levels/', GetEducationLevelsRequestHandler::class);
    }
}

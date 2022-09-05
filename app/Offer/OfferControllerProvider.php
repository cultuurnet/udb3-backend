<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Http\Offer\AddImageRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelFromJsonBodyRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\CurrentUserHasPermissionRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteTypicalAgeRangeRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetCalendarSummaryRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetHistoryRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetPermissionsForCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Offer\GivenUserHasPermissionRequestHandler;
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
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\User\CurrentUser;
use Ramsey\Uuid\UuidFactory;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

final class OfferControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->delete('/{offerType}/{offerId}/', DeleteRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/name/{language}/', UpdateTitleRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/{language}/title/', UpdateTitleRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/description/{language}/', UpdateDescriptionRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/available-from/', UpdateAvailableFromRequestHandler::class);

        $controllers->get('/{offerType}/{offerId}/history/', GetHistoryRequestHandler::class);

        $controllers->get('/{offerType}/{offerId}/permissions/', GetPermissionsForCurrentUserRequestHandler::class);
        $controllers->get('/{offerType}/{offerId}/permissions/{userId}/', GetPermissionsForGivenUserRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/calendar/', UpdateCalendarRequestHandler::class);
        $controllers->get('/{offerType}/{offerId}/calendar-summary/', GetCalendarSummaryRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/contact-point/', UpdateContactPointRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/status/', UpdateStatusRequestHandler::class);
        $controllers->put('/{offerType}/{offerId}/booking-availability/', UpdateBookingAvailabilityRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/type/{termId}/', UpdateTypeRequestHandler::class);
        $controllers->put('/{offerType}/{offerId}/facilities/', UpdateFacilitiesRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/typical-age-range/', UpdateTypicalAgeRangeRequestHandler::class);
        $controllers->delete('/{offerType}/{offerId}/typical-age-range/', DeleteTypicalAgeRangeRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/booking-info/', UpdateBookingInfoRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/labels/{labelName}/', AddLabelRequestHandler::class);
        $controllers->delete('/{offerType}/{offerId}/labels/{labelName}/', RemoveLabelRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/price-info/', UpdatePriceInfoRequestHandler::class);

        $controllers->post('/{offerType}/{offerId}/images/', AddImageRequestHandler::class);
        $controllers->put('/{offerType}/{offerId}/images/main/', SelectMainImageRequestHandler::class);
        $controllers->put('/{offerType}/{offerId}/images/{mediaId}/', UpdateImageRequestHandler::class);
        $controllers->delete('/{offerType}/{offerId}/images/{mediaId}/', RemoveImageRequestHandler::class);

        $controllers->post('/{offerType}/{offerId}/videos/', AddVideoRequestHandler::class);
        $controllers->patch('/{offerType}/{offerId}/videos/', UpdateVideosRequestHandler::class);
        $controllers->delete('/{offerType}/{offerId}/videos/{videoId}/', DeleteVideoRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/organizer/{organizerId}/', UpdateOrganizerRequestHandler::class);
        $controllers->delete('/{offerType}/{offerId}/organizer/{organizerId}/', DeleteOrganizerRequestHandler::class);

        $controllers->patch('/{offerType}/{offerId}/', PatchOfferRequestHandler::class);

        /**
         * Legacy routes that we need to keep for backward compatibility.
         */
        $controllers->get('/{offerType}/{offerId}/permission/', CurrentUserHasPermissionRequestHandler::class);
        $controllers->get('/{offerType}/{offerId}/permission/{userId}/', GivenUserHasPermissionRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/typical-age-range/', UpdateTypicalAgeRangeRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/booking-info/', UpdateBookingInfoRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/contact-point/', UpdateContactPointRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/organizer/', UpdateOrganizerFromJsonBodyRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/images/main/', SelectMainImageRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/images/{mediaId}/', UpdateImageRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/labels/', AddLabelFromJsonBodyRequestHandler::class);
        $controllers->post('/{offerType}/{offerId}/{language}/description/', UpdateDescriptionRequestHandler::class);

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[GetDetailRequestHandler::class] = $app->share(
            fn (Application $app) => new GetDetailRequestHandler($app[OfferJsonDocumentReadRepository::class])
        );

        $app[DeleteRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteRequestHandler($app['event_command_bus'])
        );

        $app[UpdateTypicalAgeRangeRequestHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateTypicalAgeRangeRequestHandler($app['event_command_bus']);
            }
        );

        $app[DeleteTypicalAgeRangeRequestHandler::class] = $app->share(
            function (Application $app) {
                return new DeleteTypicalAgeRangeRequestHandler($app['event_command_bus']);
            }
        );

        $app[AddLabelRequestHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelRequestHandler($app['event_command_bus']);
            }
        );

        $app[RemoveLabelRequestHandler::class] = $app->share(
            function (Application $app) {
                return new RemoveLabelRequestHandler($app['event_command_bus']);
            }
        );

        $app[AddLabelFromJsonBodyRequestHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelFromJsonBodyRequestHandler(
                    $app['event_command_bus'],
                    new LabelJSONDeserializer()
                );
            }
        );

        $app[UpdateBookingInfoRequestHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateBookingInfoRequestHandler($app['event_command_bus']);
            }
        );

        $app[UpdateContactPointRequestHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateContactPointRequestHandler($app['event_command_bus']);
            }
        );

        $app[UpdateTitleRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateTitleRequestHandler($app['event_command_bus'])
        );

        $app[UpdateDescriptionRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateDescriptionRequestHandler(
                $app['event_command_bus'],
                new DescriptionJSONDeserializer()
            )
        );

        $app[UpdateAvailableFromRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateAvailableFromRequestHandler($app['event_command_bus'])
        );

        $app[GetHistoryRequestHandler::class] = $app->share(
            fn (Application $app) => new GetHistoryRequestHandler(
                $app['event_history_repository'],
                $app['places_history_repository'],
                $app[CurrentUser::class]->isGodUser()
            )
        );

        $app[GetPermissionsForCurrentUserRequestHandler::class] = $app->share(
            fn (Application $app) => new GetPermissionsForCurrentUserRequestHandler(
                $app['offer_permission_voter'],
                $app[CurrentUser::class]->getId()
            )
        );

        $app[GetPermissionsForGivenUserRequestHandler::class] = $app->share(
            fn (Application $app) => new GetPermissionsForGivenUserRequestHandler(
                $app['offer_permission_voter']
            )
        );

        $app[CurrentUserHasPermissionRequestHandler::class] = $app->share(
            fn (Application $app) => new CurrentUserHasPermissionRequestHandler(
                Permission::aanbodBewerken(),
                $app['offer_permission_voter'],
                $app[CurrentUser::class]->getId()
            )
        );

        $app[GivenUserHasPermissionRequestHandler::class] = $app->share(
            fn (Application $app) => new GivenUserHasPermissionRequestHandler(
                Permission::aanbodBewerken(),
                $app['offer_permission_voter']
            )
        );

        $app[UpdateOrganizerRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateOrganizerRequestHandler(
                $app['event_command_bus'],
                $app['organizer_jsonld_repository']
            )
        );

        $app[UpdateOrganizerFromJsonBodyRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateOrganizerFromJsonBodyRequestHandler(
                $app['event_command_bus'],
                $app['organizer_jsonld_repository']
            )
        );

        $app[DeleteOrganizerRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteOrganizerRequestHandler(
                $app['event_command_bus']
            )
        );

        $app[UpdateCalendarRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateCalendarRequestHandler($app['event_command_bus'])
        );

        $app[GetCalendarSummaryRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCalendarSummaryRequestHandler($app[OfferJsonDocumentReadRepository::class])
        );

        $app[UpdateStatusRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateStatusRequestHandler($app['event_command_bus'])
        );

        $app[UpdateBookingAvailabilityRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateBookingAvailabilityRequestHandler($app['event_command_bus'])
        );

        $app[UpdateTypeRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateTypeRequestHandler($app['event_command_bus'])
        );

        $app[UpdateFacilitiesRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateFacilitiesRequestHandler($app['event_command_bus'])
        );

        $app[UpdatePriceInfoRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdatePriceInfoRequestHandler($app['event_command_bus'])
        );

        $app[AddImageRequestHandler::class] = $app->share(
            fn (Application $app) => new AddImageRequestHandler(
                $app['event_command_bus']
            )
        );

        $app[SelectMainImageRequestHandler::class] = $app->share(
            fn (Application $app) => new SelectMainImageRequestHandler(
                $app['event_command_bus'],
                $app['media_manager']
            )
        );

        $app[UpdateImageRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateImageRequestHandler(
                $app['event_command_bus']
            )
        );

        $app[RemoveImageRequestHandler::class] = $app->share(
            fn (Application $app) => new RemoveImageRequestHandler(
                $app['event_command_bus'],
                $app['media_manager']
            )
        );

        $app[AddVideoRequestHandler::class] = $app->share(
            fn (Application $app) => new AddVideoRequestHandler(
                $app['event_command_bus'],
                new UuidFactory()
            )
        );

        $app[UpdateVideosRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateVideosRequestHandler(
                $app['event_command_bus']
            )
        );

        $app[DeleteVideoRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteVideoRequestHandler(
                $app['event_command_bus']
            )
        );

        $app[PatchOfferRequestHandler::class] = $app->share(
            fn (Application $app) => new PatchOfferRequestHandler($app['event_command_bus'])
        );
    }

    public function boot(Application $app): void
    {
    }
}

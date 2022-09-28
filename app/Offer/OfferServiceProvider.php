<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\DescriptionJSONDeserializer;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
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
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOfferHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportVideosHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateAvailableFromHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateBookingAvailabilityHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateCalendarHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateFacilitiesHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdatePriceInfoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateStatusHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateTitleHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateTypeHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateVideoHandler;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\Popularity\DBALPopularityRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ProcessManagers\AutoApproveForUiTIDv1ApiKeysProcessManager;
use CultuurNet\UDB3\Offer\ProcessManagers\RelatedDocumentProjectedToJSONLDDispatcher;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataProjector;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\UiTPAS\Validation\EventHasTicketSalesGuard;
use CultuurNet\UDB3\User\CurrentUser;
use Ramsey\Uuid\UuidFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class OfferServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[RelatedDocumentProjectedToJSONLDDispatcher::class] = $app::share(
            fn (Application $app) => new RelatedDocumentProjectedToJSONLDDispatcher(
                $app[EventBus::class],
                $app[EventRelationsRepository::class],
                $app[PlaceRelationsRepository::class],
                $app['event_iri_generator'],
                $app['place_iri_generator'],
            )
        );

        $app[OfferJsonDocumentReadRepository::class] = $app->share(
            fn (Application $app) => new OfferJsonDocumentReadRepository(
                $app['event_jsonld_repository'],
                $app['place_jsonld_repository']
            )
        );

        $app[OfferMetadataRepository::class] = $app->share(
            function (Application $app) {
                return new OfferMetadataRepository($app['dbal_connection']);
            }
        );

        $app[OfferMetadataProjector::class] = $app->share(
            function (Application $app) {
                return new OfferMetadataProjector(
                    $app[OfferMetadataRepository::class],
                    $app['config']['api_key_consumers']
                );
            }
        );

        $app[AutoApproveForUiTIDv1ApiKeysProcessManager::class] = $app->share(
            function (Application $app) {
                return new ReplayFilteringEventListener(
                    new AutoApproveForUiTIDv1ApiKeysProcessManager(
                        $app[OfferRepository::class],
                        $app[ConsumerReadRepository::class],
                        $app['should_auto_approve_new_offer']
                    )
                );
            }
        );

        $app[PopularityRepository::class] = $app->share(
            function (Application $app) {
                return new DBALPopularityRepository(
                    $app['dbal_connection']
                );
            }
        );

        $app['iri_offer_identifier_factory'] = $app->share(
            function (Application $app) {
                return new IriOfferIdentifierFactory(
                    $app['config']['offer_url_regex']
                );
            }
        );

        $app['should_auto_approve_new_offer'] = $app->share(
            function (Application $app) {
                return new ConsumerIsInPermissionGroup(
                    (string) $app['config']['uitid']['auto_approve_group_id']
                );
            }
        );

        $app[OfferRepository::class] = $app->share(
            function (Application $app) {
                return new OfferRepository(
                    $app['event_repository'],
                    $app['place_repository']
                );
            }
        );

        $app[EventHasTicketSalesGuard::class] = $app->share(
            fn (Application $app) => new EventHasTicketSalesGuard(
                $app['uitpas'],
                $app['event_repository'],
                LoggerFactory::create($app, LoggerName::forService('uitpas', 'ticket-sales'))
            )
        );

        $app[UpdateTitleHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateTitleHandler($app[OfferRepository::class]);
            }
        );

        $app[UpdateAvailableFromHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateAvailableFromHandler($app[OfferRepository::class]);
            }
        );

        $app[UpdateCalendarHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateCalendarHandler($app[OfferRepository::class]);
            }
        );

        $app[UpdateStatusHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateStatusHandler($app[OfferRepository::class]);
            }
        );

        $app[UpdateBookingAvailabilityHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateBookingAvailabilityHandler($app[OfferRepository::class]);
            }
        );

        $app[UpdateTypeHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateTypeHandler($app[OfferRepository::class]);
            }
        );

        $app[UpdateFacilitiesHandler::class] = $app->share(
            function (Application $app) {
                return new UpdateFacilitiesHandler($app[OfferRepository::class]);
            }
        );

        $app[ChangeOwnerHandler::class] = $app->share(
            function (Application $app) {
                return new ChangeOwnerHandler(
                    $app[OfferRepository::class],
                    $app['offer_owner_query']
                );
            }
        );

        $app[AddLabelHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelHandler(
                    $app[OfferRepository::class],
                    $app['labels.constraint_aware_service'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );
            }
        );

        $app[RemoveLabelHandler::class] = $app->share(
            function (Application $app) {
                return new RemoveLabelHandler($app[OfferRepository::class]);
            }
        );

        $app[ImportLabelsHandler::class] = $app->share(
            function (Application $app) {
                return new ImportLabelsHandler(
                    $app[OfferRepository::class],
                    new LabelImportPreProcessor(
                        $app['labels.constraint_aware_service'],
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[CurrentUser::class]->getId()
                    )
                );
            }
        );

        $app[AddVideoHandler::class] = $app->share(
            fn (Application $app) => new AddVideoHandler($app[OfferRepository::class])
        );

        $app[UpdateVideoHandler::class] = $app->share(
            fn (Application $app) => new UpdateVideoHandler($app[OfferRepository::class])
        );

        $app[DeleteVideoHandler::class] = $app->share(
            fn (Application $application) => new DeleteVideoHandler($app[OfferRepository::class])
        );

        $app[ImportVideosHandler::class] = $app->share(
            fn (Application $app) => new ImportVideosHandler($app[OfferRepository::class])
        );

        $app[DeleteOfferHandler::class] = $app->share(
            fn (Application $application) => new DeleteOfferHandler($app[OfferRepository::class])
        );

        $app[UpdatePriceInfoHandler::class] = $app->share(
            fn (Application $application) => new UpdatePriceInfoHandler($app[OfferRepository::class])
        );

        $app[UpdateOrganizerHandler::class] = $app->share(
            fn (Application $application) => new UpdateOrganizerHandler(
                $app[OfferRepository::class],
                $app[EventHasTicketSalesGuard::class]
            )
        );

        $app[DeleteOrganizerHandler::class] = $app->share(
            fn (Application $application) => new DeleteOrganizerHandler(
                $app[OfferRepository::class],
                $app[EventHasTicketSalesGuard::class]
            )
        );

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

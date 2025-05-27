<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\ReadModel\RDF\EventJsonToTurtleConverter;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Http\Offer\AddImageRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelFromJsonBodyRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\CurrentUserHasPermissionRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteTypicalAgeRangeRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetCalendarSummaryRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetContributorsRequestHandler;
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
use CultuurNet\UDB3\Http\Offer\UpdateContributorsRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateDescriptionRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateFacilitiesRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateImageRequestHandler;
use CultuurNet\UDB3\Http\Offer\ReplaceLabelsRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateOrganizerFromJsonBodyRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdatePriceInfoRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateStatusRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateTypeRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateTypicalAgeRangeRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateVideosRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateWorkflowStatusRequestHandler;
use CultuurNet\UDB3\Http\RDF\TurtleResponseFactory;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\GeneratedUuidFactory;
use CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteCurrentOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteDescriptionHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOfferHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportVideosHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ReplaceLabelsHandler;
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
use CultuurNet\UDB3\Offer\Popularity\DBALPopularityRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ProcessManagers\AutoApproveForUiTIDv1ApiKeysProcessManager;
use CultuurNet\UDB3\Offer\ProcessManagers\RelatedDocumentProjectedToJSONLDDispatcher;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataProjector;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\Place\ReadModel\RDF\PlaceJsonToTurtleConverter;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\DeleteUiTPASPlaceVoter;
use CultuurNet\UDB3\UiTPAS\Validation\EventHasTicketSalesGuard;
use CultuurNet\UDB3\User\CurrentUser;

final class OfferServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RelatedDocumentProjectedToJSONLDDispatcher::class,
            OfferJsonDocumentReadRepository::class,
            OfferMetadataRepository::class,
            OfferMetadataProjector::class,
            AutoApproveForUiTIDv1ApiKeysProcessManager::class,
            PopularityRepository::class,
            'iri_offer_identifier_factory',
            'should_auto_approve_new_offer',
            OfferRepository::class,
            EventHasTicketSalesGuard::class,
            UpdateTitleHandler::class,
            UpdateAvailableFromHandler::class,
            UpdateCalendarHandler::class,
            UpdateStatusHandler::class,
            UpdateBookingAvailabilityHandler::class,
            UpdateTypeHandler::class,
            UpdateFacilitiesHandler::class,
            ChangeOwnerHandler::class,
            AddLabelHandler::class,
            RemoveLabelHandler::class,
            ImportLabelsHandler::class,
            AddVideoHandler::class,
            UpdateVideoHandler::class,
            DeleteVideoHandler::class,
            ImportVideosHandler::class,
            DeleteOfferHandler::class,
            UpdatePriceInfoHandler::class,
            UpdateOrganizerHandler::class,
            DeleteOrganizerHandler::class,
            DeleteCurrentOrganizerHandler::class,
            GetDetailRequestHandler::class,
            DeleteRequestHandler::class,
            UpdateTypicalAgeRangeRequestHandler::class,
            DeleteTypicalAgeRangeRequestHandler::class,
            AddLabelRequestHandler::class,
            RemoveLabelRequestHandler::class,
            AddLabelFromJsonBodyRequestHandler::class,
            ReplaceLabelsRequestHandler::class,
            UpdateBookingInfoRequestHandler::class,
            UpdateContactPointRequestHandler::class,
            UpdateTitleRequestHandler::class,
            UpdateDescriptionRequestHandler::class,
            DeleteDescriptionRequestHandler::class,
            DeleteDescriptionHandler::class,
            UpdateAvailableFromRequestHandler::class,
            GetHistoryRequestHandler::class,
            GetPermissionsForCurrentUserRequestHandler::class,
            GetPermissionsForGivenUserRequestHandler::class,
            CurrentUserHasPermissionRequestHandler::class,
            GivenUserHasPermissionRequestHandler::class,
            UpdateOrganizerRequestHandler::class,
            UpdateOrganizerFromJsonBodyRequestHandler::class,
            DeleteOrganizerRequestHandler::class,
            UpdateCalendarRequestHandler::class,
            GetCalendarSummaryRequestHandler::class,
            UpdateStatusRequestHandler::class,
            UpdateBookingAvailabilityRequestHandler::class,
            UpdateTypeRequestHandler::class,
            UpdateFacilitiesRequestHandler::class,
            UpdatePriceInfoRequestHandler::class,
            AddImageRequestHandler::class,
            SelectMainImageRequestHandler::class,
            UpdateImageRequestHandler::class,
            RemoveImageRequestHandler::class,
            AddVideoRequestHandler::class,
            UpdateVideosRequestHandler::class,
            DeleteVideoRequestHandler::class,
            UpdateWorkflowStatusRequestHandler::class,
            UpdateContributorsRequestHandler::class,
            GetContributorsRequestHandler::class,
            PatchOfferRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            RelatedDocumentProjectedToJSONLDDispatcher::class,
            fn () => new RelatedDocumentProjectedToJSONLDDispatcher(
                $container->get(EventBus::class),
                $container->get(EventRelationsRepository::class),
                $container->get(PlaceRelationsRepository::class),
                $container->get('event_iri_generator'),
                $container->get('place_iri_generator')
            )
        );

        $container->addShared(
            OfferJsonDocumentReadRepository::class,
            fn () => new OfferJsonDocumentReadRepository(
                $container->get('event_jsonld_repository'),
                $container->get('place_jsonld_repository')
            )
        );

        $container->addShared(
            OfferMetadataRepository::class,
            fn () => new OfferMetadataRepository($container->get('dbal_connection'))
        );

        $container->addShared(
            OfferMetadataProjector::class,
            fn () => new OfferMetadataProjector(
                $container->get(OfferMetadataRepository::class),
                $container->get('config')['api_key_consumers']
            )
        );

        $container->addShared(
            AutoApproveForUiTIDv1ApiKeysProcessManager::class,
            fn () => new ReplayFilteringEventListener(
                new AutoApproveForUiTIDv1ApiKeysProcessManager(
                    $container->get(OfferRepository::class),
                    $container->get(ConsumerReadRepository::class),
                    $container->get('should_auto_approve_new_offer')
                )
            )
        );

        $container->addShared(
            PopularityRepository::class,
            fn () => new DBALPopularityRepository($container->get('dbal_connection'))
        );

        $container->addShared(
            'iri_offer_identifier_factory',
            fn () => new IriOfferIdentifierFactory($container->get('config')['offer_url_regex'])
        );

        $container->addShared(
            'should_auto_approve_new_offer',
            fn () => new ConsumerIsInPermissionGroup(
                (string) $container->get('config')['uitid']['auto_approve_group_id']
            )
        );

        $container->addShared(
            OfferRepository::class,
            fn () => new OfferRepository(
                $container->get('event_repository'),
                $container->get('place_repository')
            )
        );

        $container->addShared(
            EventHasTicketSalesGuard::class,
            fn () => new EventHasTicketSalesGuard(
                $container->get('uitpas'),
                $container->get('event_repository'),
                LoggerFactory::create($container, LoggerName::forService('uitpas', 'ticket-sales'))
            )
        );

        $container->addShared(
            UpdateTitleHandler::class,
            fn () => new UpdateTitleHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateAvailableFromHandler::class,
            fn () => new UpdateAvailableFromHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateCalendarHandler::class,
            fn () => new UpdateCalendarHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateStatusHandler::class,
            fn () => new UpdateStatusHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateBookingAvailabilityHandler::class,
            fn () => new UpdateBookingAvailabilityHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateTypeHandler::class,
            fn () => new UpdateTypeHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateFacilitiesHandler::class,
            fn () => new UpdateFacilitiesHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            ChangeOwnerHandler::class,
            fn () => new ChangeOwnerHandler(
                $container->get(OfferRepository::class),
                $container->get('offer_owner_query')
            )
        );

        $container->addShared(
            AddLabelHandler::class,
            fn () => new AddLabelHandler(
                $container->get(OfferRepository::class),
                $container->get('labels.constraint_aware_service'),
                $container->get(LabelServiceProvider::JSON_READ_REPOSITORY)
            )
        );

        $container->addShared(
            RemoveLabelHandler::class,
            fn () => new RemoveLabelHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            ImportLabelsHandler::class,
            fn () => new ImportLabelsHandler(
                $container->get(OfferRepository::class),
                new LabelImportPreProcessor(
                    $container->get('labels.constraint_aware_service'),
                    $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                    $container->get(CurrentUser::class)->getId()
                )
            )
        );

        $container->addShared(
            ReplaceLabelsHandler::class,
            fn () => new ReplaceLabelsHandler(
                $container->get(OfferRepository::class),
                new LabelImportPreProcessor(
                    $container->get('labels.constraint_aware_service'),
                    $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                    $container->get(CurrentUser::class)->getId()
                )
            )
        );

        $container->addShared(
            AddVideoHandler::class,
            fn () => new AddVideoHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateVideoHandler::class,
            fn () => new UpdateVideoHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            DeleteVideoHandler::class,
            fn () => new DeleteVideoHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            ImportVideosHandler::class,
            fn () => new ImportVideosHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            DeleteOfferHandler::class,
            fn () => new DeleteOfferHandler(
                $container->get(OfferRepository::class),
                $container->get(DeleteUiTPASPlaceVoter::class),
                $container->get(CurrentUser::class)->getId()
            )
        );

        $container->addShared(
            UpdatePriceInfoHandler::class,
            fn () => new UpdatePriceInfoHandler($container->get(OfferRepository::class))
        );

        $container->addShared(
            UpdateOrganizerHandler::class,
            fn () => new UpdateOrganizerHandler(
                $container->get(OfferRepository::class),
                $container->get(EventHasTicketSalesGuard::class)
            )
        );

        $container->addShared(
            DeleteOrganizerHandler::class,
            fn () => new DeleteOrganizerHandler(
                $container->get(OfferRepository::class),
                $container->get(EventHasTicketSalesGuard::class)
            )
        );

        $container->addShared(
            DeleteCurrentOrganizerHandler::class,
            fn () => new DeleteCurrentOrganizerHandler(
                $container->get(OfferRepository::class),
                $container->get(EventHasTicketSalesGuard::class)
            )
        );

        $container->addShared(
            GetDetailRequestHandler::class,
            fn () => new GetDetailRequestHandler(
                $container->get(OfferJsonDocumentReadRepository::class),
                new TurtleResponseFactory($container->get(PlaceJsonToTurtleConverter::class)),
                new TurtleResponseFactory($container->get(EventJsonToTurtleConverter::class))
            )
        );

        $container->addShared(
            DeleteRequestHandler::class,
            fn () => new DeleteRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateTypicalAgeRangeRequestHandler::class,
            fn () => new UpdateTypicalAgeRangeRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            DeleteTypicalAgeRangeRequestHandler::class,
            fn () => new DeleteTypicalAgeRangeRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            AddLabelRequestHandler::class,
            fn () => new AddLabelRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            RemoveLabelRequestHandler::class,
            fn () => new RemoveLabelRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            AddLabelFromJsonBodyRequestHandler::class,
            fn () => new AddLabelFromJsonBodyRequestHandler(
                $container->get('event_command_bus'),
                new LabelJSONDeserializer()
            )
        );

        $container->addShared(
            ReplaceLabelsRequestHandler::class,
            fn () => new ReplaceLabelsRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateBookingInfoRequestHandler::class,
            fn () => new UpdateBookingInfoRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateContactPointRequestHandler::class,
            fn () => new UpdateContactPointRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateTitleRequestHandler::class,
            fn () => new UpdateTitleRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateDescriptionRequestHandler::class,
            fn () => new UpdateDescriptionRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            DeleteDescriptionRequestHandler::class,
            fn () => new DeleteDescriptionRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            DeleteDescriptionHandler::class,
            function () use ($container) {
                $handler = new DeleteDescriptionHandler($container->get(OfferRepository::class));
                $handler->setLogger(LoggerFactory::create($container, LoggerName::forWeb()));
                return $handler;
            }
        );

        $container->addShared(
            UpdateAvailableFromRequestHandler::class,
            fn () => new UpdateAvailableFromRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            GetHistoryRequestHandler::class,
            fn () => new GetHistoryRequestHandler(
                $container->get('event_history_repository'),
                $container->get('places_history_repository'),
                $container->get(CurrentUser::class)->isGodUser()
            )
        );

        $container->addShared(
            GetPermissionsForCurrentUserRequestHandler::class,
            fn () => new GetPermissionsForCurrentUserRequestHandler(
                $container->get('offer_permission_voter'),
                $container->get(CurrentUser::class)->getId()
            )
        );

        $container->addShared(
            GetPermissionsForGivenUserRequestHandler::class,
            fn () => new GetPermissionsForGivenUserRequestHandler($container->get('offer_permission_voter'))
        );

        $container->addShared(
            CurrentUserHasPermissionRequestHandler::class,
            fn () => new CurrentUserHasPermissionRequestHandler(
                Permission::aanbodBewerken(),
                $container->get('offer_permission_voter'),
                $container->get(CurrentUser::class)->getId()
            )
        );

        $container->addShared(
            GivenUserHasPermissionRequestHandler::class,
            fn () => new GivenUserHasPermissionRequestHandler(
                Permission::aanbodBewerken(),
                $container->get('offer_permission_voter')
            )
        );

        $container->addShared(
            UpdateOrganizerRequestHandler::class,
            fn () => new UpdateOrganizerRequestHandler(
                $container->get('event_command_bus'),
                $container->get('organizer_jsonld_repository')
            )
        );

        $container->addShared(
            UpdateOrganizerFromJsonBodyRequestHandler::class,
            fn () => new UpdateOrganizerFromJsonBodyRequestHandler(
                $container->get('event_command_bus'),
                $container->get('organizer_jsonld_repository')
            )
        );

        $container->addShared(
            DeleteOrganizerRequestHandler::class,
            fn () => new DeleteOrganizerRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateCalendarRequestHandler::class,
            fn () => new UpdateCalendarRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            GetCalendarSummaryRequestHandler::class,
            fn () => new GetCalendarSummaryRequestHandler($container->get(OfferJsonDocumentReadRepository::class))
        );

        $container->addShared(
            UpdateStatusRequestHandler::class,
            fn () => new UpdateStatusRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateBookingAvailabilityRequestHandler::class,
            fn () => new UpdateBookingAvailabilityRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateTypeRequestHandler::class,
            fn () => new UpdateTypeRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateFacilitiesRequestHandler::class,
            fn () => new UpdateFacilitiesRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdatePriceInfoRequestHandler::class,
            fn () => new UpdatePriceInfoRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            AddImageRequestHandler::class,
            fn () => new AddImageRequestHandler(
                $container->get('event_command_bus'),
                $container->get('media_object_repository'),
            )
        );

        $container->addShared(
            SelectMainImageRequestHandler::class,
            fn () => new SelectMainImageRequestHandler(
                $container->get('event_command_bus'),
                $container->get('media_manager')
            )
        );

        $container->addShared(
            UpdateImageRequestHandler::class,
            fn () => new UpdateImageRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            RemoveImageRequestHandler::class,
            fn () => new RemoveImageRequestHandler(
                $container->get('event_command_bus'),
                $container->get('media_manager')
            )
        );

        $container->addShared(
            AddVideoRequestHandler::class,
            fn () => new AddVideoRequestHandler(
                $container->get('event_command_bus'),
                new GeneratedUuidFactory()
            )
        );

        $container->addShared(
            UpdateVideosRequestHandler::class,
            fn () => new UpdateVideosRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            DeleteVideoRequestHandler::class,
            fn () => new DeleteVideoRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateWorkflowStatusRequestHandler::class,
            fn () => new UpdateWorkflowStatusRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            GetContributorsRequestHandler::class,
            fn () => new GetContributorsRequestHandler(
                $container->get(OfferRepository::class),
                $container->get(ContributorRepository::class),
                $container->get('offer_permission_voter'),
                $container->get(CurrentUser::class)->getId()
            )
        );

        $container->addShared(
            UpdateContributorsRequestHandler::class,
            fn () => new UpdateContributorsRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            PatchOfferRequestHandler::class,
            fn () => new PatchOfferRequestHandler($container->get('event_command_bus'))
        );
    }
}

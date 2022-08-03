<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
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
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\UiTPAS\Validation\EventHasTicketSalesGuard;
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
                $app['place_relations_repository'],
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
                        $app['auth.consumer_repository'],
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
                        $app['current_user_id']
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
    }


    public function boot(Application $app): void
    {
    }
}

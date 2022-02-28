<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerIsInPermissionGroup;
use CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOfferHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportVideosHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateAvailableFromHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateBookingAvailabilityHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateCalendarHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateFacilitiesHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateStatusHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateTypeHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateVideoHandler;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Offer\Popularity\DBALPopularityRepository;
use CultuurNet\UDB3\Offer\Popularity\PopularityRepository;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataProjector;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OfferServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
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
                    $app['labels.constraint_aware_service'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app['labels.labels_locked_for_import_repository'],
                    $app['current_user_id']
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
    }


    public function boot(Application $app): void
    {
    }
}

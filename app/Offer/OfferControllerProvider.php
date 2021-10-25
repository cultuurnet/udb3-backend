<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Http\Offer\AddVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\DeleteVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetCalendarSummaryRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetHistoryRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateBookingAvailabilityRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateCalendarRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateStatusRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateVideosRequestHandler;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
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

        $controllers->get('/{offerType}/{offerId}/', GetDetailRequestHandler::class);

        $controllers->get('/{offerType}/{offerId}/history/', GetHistoryRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/calendar/', UpdateCalendarRequestHandler::class);
        $controllers->get('/{offerType}/{offerId}/calendar-summary', GetCalendarSummaryRequestHandler::class);

        $controllers->put('/{offerType}/{offerId}/status/', UpdateStatusRequestHandler::class);
        $controllers->put('/{offerType}/{offerId}/booking-availability/', UpdateBookingAvailabilityRequestHandler::class);

        $controllers->post('/{offerType}/{offerId}/videos/', AddVideoRequestHandler::class);
        $controllers->patch('/{offerType}/{offerId}/videos/', UpdateVideosRequestHandler::class);
        $controllers->delete('/{offerType}/{offerId}/videos/{videoId}', DeleteVideoRequestHandler::class);

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[GetDetailRequestHandler::class] = $app->share(
            fn (Application $app) => new GetDetailRequestHandler($app[OfferJsonDocumentReadRepository::class])
        );

        $app[GetHistoryRequestHandler::class] = $app->share(
            fn (Application $app) => new GetHistoryRequestHandler(
                $app['event_history_repository'],
                $app['places_history_repository'],
                $app['current_user_is_god_user']
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
    }

    public function boot(Application $app): void
    {
    }
}

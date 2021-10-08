<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Http\Offer\AddVideoRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetCalendarSummaryRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateBookingAvailabilityRequestHandler;
use CultuurNet\UDB3\Http\Offer\UpdateStatusRequestHandler;
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

        $controllers->get('/{offerType}/{offerId}/calendar-summary', GetCalendarSummaryRequestHandler::class)
            ->assert('offerType', '(events|places)');

        $controllers->put('/{offerType}/{offerId}/status/', UpdateStatusRequestHandler::class)
            ->assert('offerType', '(events|places)');

        $controllers->put('/{offerType}/{offerId}/booking-availability/', UpdateBookingAvailabilityRequestHandler::class)
            ->assert('offerType', '(events|places)');

        $controllers->post('/{offerType}/{offerId}/videos/', AddVideoRequestHandler::class)
            ->assert('offerType', '(events|places)');

        return $controllers;
    }

    public function register(Application $app): void
    {
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
            fn (Application $app) => new AddVideoRequestHandler($app['event_command_bus'], new UuidFactory())
        );
    }

    public function boot(Application $app): void
    {
    }
}

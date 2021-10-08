<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Http\Offer\GetCalendarSummaryRequestHandler;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
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

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[GetCalendarSummaryRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCalendarSummaryRequestHandler($app[OfferJsonDocumentReadRepository::class])
        );
    }

    public function boot(Application $app): void
    {
    }
}

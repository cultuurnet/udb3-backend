<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Offer\BulkLabelCommandHandler;
use CultuurNet\UDB3\Offer\OfferType;
use Silex\Application;
use Silex\ServiceProviderInterface;

class BulkLabelOfferServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['bulk_label_offer_command_handler'] = $app->share(
            function (Application $app) {
                return (new BulkLabelCommandHandler($app['search_results_generator']))
                    ->withRepository(OfferType::EVENT(), $app['event_repository'])
                    ->withRepository(OfferType::PLACE(), $app['place_repository']);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}

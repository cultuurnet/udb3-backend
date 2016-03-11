<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OfferServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['iri_offer_identifier_factory'] = $app->share(
            function (Application $app) {
                return new IriOfferIdentifierFactory(
                    $app['config']['offer_url_regex']
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}

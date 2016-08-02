<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Place;

use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceLookupServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['place_lookup'] = $app->share(
            function ($app) {
                // At the moment, the index.repository service maintains
                // an index of data for various purposes.
                return $app['index.repository'];
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}

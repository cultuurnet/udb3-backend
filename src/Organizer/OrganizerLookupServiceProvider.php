<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerLookupServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['organizer_lookup'] = $app->share(
            function (Application $app) {
                // At the moment, the index.repository service maintains
                // an index of data for various purposes.
                return $app['index.repository'];
            }
        );
    }

    public function boot(Application $app)
    {

    }
}

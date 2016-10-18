<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Search\OrganizerSearchProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['organizer_search_projector'] = $app->share(
            function (Application $app) {
                return new OrganizerSearchProjector(
                    $app['organizer_jsonld_repository'],
                    $app['organizer_elasticsearch_repository']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

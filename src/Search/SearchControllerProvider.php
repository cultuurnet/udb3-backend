<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Search;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Symfony\Search\SearchController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['search_controller'] = $app->share(
            function (Application $app) {
                return new SearchController(
                    $app['cached_search_service']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('api/1.0/search', 'search_controller:search')
            ->bind('api/1.0/search');

        return $controllers;
    }

}

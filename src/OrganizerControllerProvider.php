<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Organizer\ReadModel\Lookup\OrganizerLookupServiceInterface;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class OrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/api/1.0/organizer/suggest/{term}',
            function (Request $request, $term, Application $app) {
                /** @var OrganizerLookupServiceInterface $organizerLookupService */
                $organizerLookupService = $app['organizer_lookup'];

                // @todo Add & process pagination parameters

                $ids = $organizerLookupService->findOrganizersByPartOfTitle($term);

                $members = [];
                if (!empty($ids)) {
                    /** @var EntityServiceInterface $organizerService */
                    $organizerService = $app['organizer_service'];

                    $members = array_map(
                        function ($id) use ($organizerService) {
                            return json_decode($organizerService->getEntity($id));
                        },
                        $ids
                    );
                }

                $pagedCollection = new PagedCollection(
                    1,
                    1000,
                    $members,
                    count($members)
                );

                return (new JsonLdResponse($pagedCollection));
            }
        );

        return $controllers;
    }
}

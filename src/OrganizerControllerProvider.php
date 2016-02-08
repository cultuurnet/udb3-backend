<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Organizer\OrganizerEditingServiceInterface;
use CultuurNet\UDB3\Organizer\ReadModel\Lookup\OrganizerLookupServiceInterface;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Title;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get(
                '/organizer/{cdbid}',
                function (Request $request, Application $app, $cdbid) {
                    /** @var \CultuurNet\UDB3\EntityServiceInterface $service */
                    $service = $app['organizer_service'];

                    $organizer = $service->getEntity($cdbid);

                    $response = JsonLdResponse::create()
                        ->setContent($organizer)
                        ->setPublic()
                        ->setClientTtl(60 * 30)
                        ->setTtl(60 * 5);

                    $response->headers->set('Vary', 'Origin');

                    return $response;
                }
            )
            ->bind('organizer');

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

        $controllers->post(
            '/api/1.0/organizer',
            function (Request $request, Application $app) {
                $response = new JsonResponse();
                $body_content = json_decode($request->getContent());

                try {

                    if (empty($body_content->name)) {
                        throw new \InvalidArgumentException('Required fields are missing');
                    }

                    $addresses = array();
                    if (!empty($body_content->address->streetAddress) &&
                        !empty($body_content->address->locality) &&
                        !empty($body_content->address->postalCode) &&
                        !empty($body_content->address->country)) {
                        $addresses[] = new Address(
                            $body_content->address->streetAddress,
                            $body_content->address->postalCode,
                            $body_content->address->locality,
                            $body_content->address->country
                        );
                    }

                    $phones = array();
                    $emails = array();
                    $urls = array();
                    if (!empty($body_content->contact)) {
                        foreach ($body_content->contact as $contactInfo) {
                            if ($contactInfo->type == 'phone') {
                                $phones[] = $contactInfo->value;
                            }
                            elseif ($contactInfo->type == 'email') {
                                $emails[] = $contactInfo->value;
                            }
                            elseif ($contactInfo->type == 'url') {
                                $urls[] = $contactInfo->value;
                            }
                        }
                    }

                    /** @var OrganizerEditingServiceInterface $organizerEditor */
                    $organizerEditor = $app['organizer_editing_service'];

                    $organizer_id = $organizerEditor->createOrganizer(
                        new Title($body_content->name),
                        $addresses,
                        $phones,
                        $emails,
                        $urls
                    );

                    /** @var IriGeneratorInterface $organizerIriGenerator */
                    $organizerIriGenerator = $app['organizer_iri_generator'];

                    $response->setData(
                        [
                            'organizerId' => $organizer_id,
                            'url' => $organizerIriGenerator->iri($organizer_id),
                        ]
                    );
                } catch (\Exception $e) {
                    $response->setStatusCode(400);
                    $response->setData(['error' => $e->getMessage()]);
                }

                return $response;
            }
        );


        return $controllers;
    }
}

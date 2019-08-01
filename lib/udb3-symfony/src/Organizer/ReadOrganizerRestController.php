<?php

namespace CultuurNet\UDB3\Symfony\Organizer;

use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Symfony\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Symfony\JsonLdResponse;

class ReadOrganizerRestController
{
    const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';
    const GET_ERROR_GONE = 'An error occurred while getting the event with id %s which was removed!';

    use ApiProblemJsonResponseTrait;

    /**
     * @var EntityServiceInterface
     */
    private $service;

    /**
     * OrganizerController constructor.
     * @param EntityServiceInterface           $service
     */
    public function __construct(
        EntityServiceInterface $service
    ) {
        $this->service = $service;
    }

    /**
     * Get an organizer by its cdbid.
     * @param string $cdbid
     * @return JsonLdResponse $response
     */
    public function get($cdbid)
    {
        $response = null;

        $organizer = $this->service->getEntity($cdbid);

        if ($organizer) {
            $response = JsonLdResponse::create()
                ->setContent($organizer)
                ->setPublic();

            $response->headers->set('Vary', 'Origin');
        } else {
            $response = $this->createApiProblemJsonResponseNotFound(self::GET_ERROR_NOT_FOUND, $cdbid);
        }

        return $response;
    }
}

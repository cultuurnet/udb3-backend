<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Offer\DefaultOfferEditingService;
use CultuurNet\UDB3\Http\OfferRestBaseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class EditPlaceRestController extends OfferRestBaseController
{
    /**
     * The event relations repository.
     *
     * @var EventRelationsRepository
     */
    private $eventRelationsRepository;

    /**
     * Constructs a RestController.
     *
     */
    public function __construct(
        DefaultOfferEditingService $placeEditor,
        EventRelationsRepository $eventRelationsRepository,
        MediaManagerInterface $mediaManager
    ) {
        parent::__construct($placeEditor, $mediaManager);
        $this->eventRelationsRepository = $eventRelationsRepository;
    }

    public function getEvents(string $cdbid): JsonResponse
    {
        $response = new JsonResponse();

        // Load all event relations from the database.
        $events = $this->eventRelationsRepository->getEventsLocatedAtPlace($cdbid);

        if (!empty($events)) {
            $data = ['events' => []];

            foreach ($events as $eventId) {
                $data['events'][] = [
                    '@id' => $eventId,
                ];
            }

            $response->setData($data);
        }

        return $response;
    }
}

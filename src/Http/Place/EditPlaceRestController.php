<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Place\PlaceEditingServiceInterface;
use CultuurNet\UDB3\Http\Deserializer\Address\AddressJSONDeserializer;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use CultuurNet\UDB3\Http\OfferRestBaseController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\UDB3\StringLiteral;

class EditPlaceRestController extends OfferRestBaseController
{
    /**
     * The event relations repository.
     *
     * @var RepositoryInterface
     */
    private $eventRelationsRepository;

    /**
     * @var AddressJSONDeserializer
     */
    private $addressDeserializer;

    /**
     * Constructs a RestController.
     *
     */
    public function __construct(
        PlaceEditingServiceInterface $placeEditor,
        RepositoryInterface $eventRelationsRepository,
        MediaManagerInterface $mediaManager
    ) {
        parent::__construct($placeEditor, $mediaManager);
        $this->eventRelationsRepository = $eventRelationsRepository;


        $this->addressDeserializer = new AddressJSONDeserializer();
    }

    public function placeContext(): BinaryFileResponse
    {
        $response = new BinaryFileResponse('/udb3/api/1.0/place.jsonld');
        $response->headers->set('Content-Type', 'application/ld+json');
        return $response;
    }

    public function updateAddress(Request $request, string $cdbid, string $lang): Response
    {
        $address = $this->addressDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->editor->updateAddress(
            $cdbid,
            $address,
            new Language($lang)
        );

        return new NoContent();
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

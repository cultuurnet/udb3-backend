<?php

namespace CultuurNet\UDB3\Symfony\Place;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Place\PlaceEditingServiceInterface;
use CultuurNet\UDB3\Symfony\Deserializer\Address\AddressJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\Place\CreatePlaceJSONDeserializer;
use CultuurNet\UDB3\Symfony\Deserializer\Place\MajorInfoJSONDeserializer;
use CultuurNet\UDB3\Symfony\HttpFoundation\NoContent;
use CultuurNet\UDB3\Symfony\OfferRestBaseController;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class EditPlaceRestController extends OfferRestBaseController
{
    /**
     * The event relations repository.
     *
     * @var RepositoryInterface
     */
    private $eventRelationsRepository;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var CreatePlaceJSONDeserializer
     */
    private $createPlaceJSONDeserializer;

    /**
     * @var MajorInfoJSONDeserializer
     */
    private $majorInfoDeserializer;

    /**
     * @var AddressJSONDeserializer
     */
    private $addressDeserializer;

    /**
     * @var ApiKeyReaderInterface
     */
    private $apiKeyReader;

    /**
     * @var ConsumerReadRepositoryInterface
     */
    private $consumerReadRepository;

    /**
     * @var ConsumerSpecificationInterface
     */
    private $shouldApprove;

    /**
     * Constructs a RestController.
     *
     * @param PlaceEditingServiceInterface $placeEditor
     * @param RepositoryInterface $eventRelationsRepository
     * @param MediaManagerInterface $mediaManager
     * @param IriGeneratorInterface $iriGenerator
     * @param ApiKeyReaderInterface $apiKeyReader
     * @param ConsumerReadRepositoryInterface $consumerReadRepository
     * @param ConsumerSpecificationInterface $shouldApprove
     */
    public function __construct(
        PlaceEditingServiceInterface $placeEditor,
        RepositoryInterface $eventRelationsRepository,
        MediaManagerInterface $mediaManager,
        IriGeneratorInterface $iriGenerator,
        ApiKeyReaderInterface $apiKeyReader,
        ConsumerReadRepositoryInterface $consumerReadRepository,
        ConsumerSpecificationInterface $shouldApprove
    ) {
        parent::__construct($placeEditor, $mediaManager);
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->iriGenerator = $iriGenerator;

        $this->apiKeyReader = $apiKeyReader;
        $this->consumerReadRepository = $consumerReadRepository;
        $this->shouldApprove = $shouldApprove;

        $this->createPlaceJSONDeserializer = new CreatePlaceJSONDeserializer();
        $this->majorInfoDeserializer = new MajorInfoJSONDeserializer();
        $this->addressDeserializer = new AddressJSONDeserializer();
    }

    public function placeContext(): BinaryFileResponse
    {
        $response = new BinaryFileResponse('/udb3/api/1.0/place.jsonld');
        $response->headers->set('Content-Type', 'application/ld+json');
        return $response;
    }

    public function createPlace(Request $request): JsonResponse
    {
        $majorInfo = $this->createPlaceJSONDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $apiKey = $this->apiKeyReader->read($request);

        $consumer = null;
        if ($apiKey) {
            $consumer = $this->consumerReadRepository->getConsumer($apiKey);
        }

        $approve = false;
        if ($consumer) {
            $approve = $this->shouldApprove->satisfiedBy($consumer);
        }

        $createMethod = $approve ? 'createApprovedPlace' : 'createPlace';

        $placeId = $this->editor->$createMethod(
            $majorInfo->getMainLanguage(),
            $majorInfo->getTitle(),
            $majorInfo->getType(),
            $majorInfo->getAddress(),
            $majorInfo->getCalendar(),
            $majorInfo->getTheme()
        );

        return new JsonResponse(
            [
                'placeId' => $placeId,
                'url' => $this->iriGenerator->iri($placeId),
            ],
            201
        );
    }

    public function deletePlace(string $cdbid): Response
    {
        if (empty($cdbid)) {
            throw new InvalidArgumentException('Required fields are missing');
        }

        $this->editor->deletePlace($cdbid);

        return new NoContent();
    }

    public function updateMajorInfo(Request $request, string $cdbid): Response
    {
        $majorInfo = $this->majorInfoDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->editor->updateMajorInfo(
            $cdbid,
            $majorInfo->getTitle(),
            $majorInfo->getType(),
            $majorInfo->getAddress(),
            $majorInfo->getCalendar(),
            $majorInfo->getTheme()
        );

        return new NoContent();
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

<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Management\User\UserIdentificationInterface;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HistoryPlaceRestController
{
    use ApiProblemJsonResponseTrait;

    private const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the place with id %s!';
    private const HISTORY_ERROR_GONE = 'An error occurred while getting the history of the place with id %s which was removed!';
    private const HISTORY_ERROR_FORBIDDEN = 'Forbidden to access place history.';

    /**
     * @var DocumentRepository
     */
    private $historyRepository;

    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;


    public function __construct(
        DocumentRepository $documentRepository,
        UserIdentificationInterface $userIdentification
    ) {
        $this->historyRepository = $documentRepository;
        $this->userIdentification = $userIdentification;
    }

    public function get(string $placeId): JsonResponse
    {
        if (!$this->userIdentification->isGodUser()) {
            return $this->forbiddenResponse($placeId);
        }

        try {
            $document = $this->historyRepository->get($placeId);

            if ($document === null) {
                return $this->notFoundResponse($placeId);
            }

            $history = array_reverse(
                array_values(
                    json_decode($document->getRawBody(), true) ?? []
                )
            );

            $response = JsonResponse::create()
                ->setContent(json_encode($history));
            $response->headers->set('Vary', 'Origin');
            return $response;
        } catch (DocumentGoneException $documentGoneException) {
            return $this->documentGoneResponse($placeId);
        }
    }

    private function forbiddenResponse(string $eventId): ApiProblemJsonResponse
    {
        return $this->createApiProblemJsonResponse(
            self::HISTORY_ERROR_FORBIDDEN,
            $eventId,
            Response::HTTP_FORBIDDEN
        );
    }

    private function notFoundResponse(string $eventId): ApiProblemJsonResponse
    {
        return $this->createApiProblemJsonResponseNotFound(self::HISTORY_ERROR_NOT_FOUND, $eventId);
    }

    private function documentGoneResponse(string $eventId): ApiProblemJsonResponse
    {
        return $this->createApiProblemJsonResponseGone(self::HISTORY_ERROR_GONE, $eventId);
    }
}

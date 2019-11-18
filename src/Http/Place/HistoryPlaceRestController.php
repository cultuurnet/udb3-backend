<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Http\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Management\User\UserIdentificationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HistoryPlaceRestController
{
    use ApiProblemJsonResponseTrait;

    private const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the event with id %s!';
    private const HISTORY_ERROR_GONE = 'An error occurred while getting the history of the event with id %s which was removed!';
    private const HISTORY_ERROR_FORBIDDEN = 'Forbidden to access event history.';

    /**
     * @var DocumentRepositoryInterface
     */
    private $historyRepository;

    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;


    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        UserIdentificationInterface $userIdentification
    ) {
        $this->historyRepository = $documentRepository;
        $this->userIdentification = $userIdentification;
    }

    public function get(string $eventId): JsonResponse
    {
        if (!$this->userIdentification->isGodUser()) {
            return $this->forbiddenResponse($eventId);
        }

        try {
            $document = $this->historyRepository->get($eventId);

            if ($document === null) {
                return $this->notFoundResponse($eventId);
            }

            $response = JsonResponse::create()
                ->setContent($document->getRawBody());
            $response->headers->set('Vary', 'Origin');
            return $response;
        } catch (DocumentGoneException $documentGoneException) {
            return $this->documentGoneResponse($eventId);
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

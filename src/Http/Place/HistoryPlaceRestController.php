<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Http\Management\User\UserIdentificationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HistoryPlaceRestController
{
    private const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the event with id %s!';
    private const HISTORY_ERROR_GONE = 'An error occurred while getting the history of the event with id %s which was removed!';
    private const HISTORY_ERROR_FORBIDDEN = 'Forbidden to access event history.';

    use ApiProblemJsonResponseTrait;
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
        $response = null;

        if (!$this->userIdentification->isGodUser()) {
            return $this->createApiProblemJsonResponse(
                self::HISTORY_ERROR_FORBIDDEN,
                $eventId,
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $document = $this->historyRepository->get($eventId);

            if ($document) {
                $response = JsonResponse::create()
                    ->setContent($document->getRawBody());

                $response->headers->set('Vary', 'Origin');
            } else {
                $response = $this->createApiProblemJsonResponseNotFound(self::HISTORY_ERROR_NOT_FOUND, $eventId);
            }
        } catch (DocumentGoneException $documentGoneException) {
            $response = $this->createApiProblemJsonResponseGone(self::HISTORY_ERROR_GONE, $eventId);
        }

        return $response;
    }
}

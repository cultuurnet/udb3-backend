<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class HistoryPlaceRestController
{
    private const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the place with id %s!';
    private const HISTORY_ERROR_FORBIDDEN = 'Forbidden to access place history.';

    /**
     * @var DocumentRepository
     */
    private $historyRepository;

    /**
     * @var bool
     */
    private $userIsGodUser;

    public function __construct(
        DocumentRepository $documentRepository,
        bool $userIsGodUser
    ) {
        $this->historyRepository = $documentRepository;
        $this->userIsGodUser = $userIsGodUser;
    }

    public function get(string $placeId): JsonResponse
    {
        if (!$this->userIsGodUser) {
            throw ApiProblem::custom(
                'about:blank',
                sprintf(self::HISTORY_ERROR_FORBIDDEN),
                403
            );
        }

        try {
            $document = $this->historyRepository->fetch($placeId);

            $history = array_reverse(
                array_values(
                    json_decode($document->getRawBody(), true) ?? []
                )
            );

            $response = JsonResponse::create()
                ->setContent(json_encode($history));
            $response->headers->set('Vary', 'Origin');
            return $response;
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::custom(
                'about:blank',
                sprintf(self::HISTORY_ERROR_NOT_FOUND, $placeId),
                404
            );
        }
    }
}

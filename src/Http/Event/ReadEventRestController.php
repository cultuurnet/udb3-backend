<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class ReadEventRestController
{
    private const HISTORY_ERROR_FORBIDDEN = 'Forbidden to access event history.';
    private const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the event with id %s!';

    /**
     * @var DocumentRepository
     */
    private $historyRepository;

    /**
     * @var bool
     */
    private $userIsGodUser;

    public function __construct(
        DocumentRepository $historyRepository,
        bool $userIsGodUser
    ) {
        $this->historyRepository = $historyRepository;
        $this->userIsGodUser = $userIsGodUser;
    }

    public function history(string $cdbid): JsonResponse
    {
        if (!$this->userIsGodUser) {
            throw ApiProblem::blank(
                sprintf(self::HISTORY_ERROR_FORBIDDEN),
                403
            );
        }

        try {
            $document = $this->historyRepository->fetch($cdbid);
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
            throw ApiProblem::blank(
                sprintf(self::HISTORY_ERROR_NOT_FOUND, $cdbid),
                404
            );
        }
    }
}

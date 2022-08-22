<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
use CultuurNet\UDB3\Event\Productions\SuggestionsNotFound;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SuggestProductionRequestHandler implements RequestHandlerInterface
{
    private SimilarEventsRepository $similarEventsRepository;

    private DocumentRepository $enrichedEventRepository;

    public function __construct(
        SimilarEventsRepository $similarEventsRepository,
        DocumentRepository $enrichedEventRepository
    ) {
        $this->similarEventsRepository = $similarEventsRepository;
        $this->enrichedEventRepository = $enrichedEventRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $suggestion = $this->similarEventsRepository->findNextSuggestion();
        } catch (SuggestionsNotFound $e) {
            return new NoContentResponse();
        }

        return new JsonResponse(
            [
                'events' => [
                    $this->getEventBody($suggestion->getEventOne()),
                    $this->getEventBody($suggestion->getEventTwo()),
                ],
                'similarity' => $suggestion->getSimilarity(),
            ]
        );
    }

    private function getEventBody(string $eventId): array
    {
        try {
            $event = $this->enrichedEventRepository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            throw new SuggestedEventNotFoundException($eventId);
        }

        return Json::decodeAssociatively($event->getRawBody());
    }
}

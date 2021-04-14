<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
use CultuurNet\UDB3\Event\Productions\SuggestionsNotFound;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductionSuggestionController
{
    /**
     * @var SimilarEventsRepository
     */
    private $similarEventsRepository;

    /**
     * @var DocumentRepository
     */
    private $enrichedEventRepository;

    public function __construct(
        SimilarEventsRepository $similarEventsRepository,
        DocumentRepository $enrichedEventRepository
    ) {
        $this->similarEventsRepository = $similarEventsRepository;
        $this->enrichedEventRepository = $enrichedEventRepository;
    }

    public function nextSuggestion(): Response
    {
        try {
            $suggestion = $this->similarEventsRepository->findNextSuggestion();
        } catch (SuggestionsNotFound $e) {
            return new NoContent();
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

        return json_decode($event->getRawBody(), true);
    }
}
